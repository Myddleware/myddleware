<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com	
 
 This file is part of Myddleware.
 
 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace Myddleware\RegleBundle\Classes;

use Symfony\Bridge\Monolog\Logger; // Gestion des logs
use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Accède aux services
use Doctrine\DBAL\Connection; // Connexion BDD
use Symfony\Component\HttpFoundation\Session\Session;
use Myddleware\RegleBundle\Classes\tools as MyddlewareTools; 
use Symfony\Component\Filesystem\Filesystem;

class jobcore  {
		
	public $id;
	public $message = '';
	public $createdJob = false;
	
	protected $container;
	protected $connection;
	protected $logger;
	protected $tools;
	
	protected $rule;
	protected $ruleId;
	protected $limit = 100;
	protected $logData;
	protected $start;
	protected $paramJob;
	protected $manual;
	protected $env;
	protected $nbDayClearJob = 7;

	public function __construct(Logger $logger, Container $container, Connection $dbalConnection) {				
		$this->logger = $logger; // gestion des logs symfony monolog
		$this->container = $container;
		$this->connection = $dbalConnection;
		$this->tools = new MyddlewareTools($this->logger, $this->container, $this->connection);	
		
		$this->env = $this->container->getParameter("kernel.environment");
		$this->setManual();
	}
		
	// Permet de charger toutes les données de la règle (en paramètre)
	// $filter peut être le rule name slug ou bien l'id de la règle
	public function setRule($filter) {
		try {
			include_once 'rule.php';
			
			// RECUPERE CONNECTEUR ID
		    $sqlRule = "SELECT * 
		    		FROM Rule 
		    		WHERE 
							(
								name_slug = :filter
							 OR id = :filter	
							)
						AND deleted = 0
					";
		    $stmt = $this->connection->prepare($sqlRule);
			$stmt->bindValue("filter", $filter);
		    $stmt->execute();	    
			$rule = $stmt->fetch(); // 1 row
			if (empty($rule['id'])) {
				throw new \Exception ('Rule '.$filter.' doesn\'t exist or is deleted.');
			}
			// Error if the rule is inactive and if we try to run it from a job (not manually)
			elseif(
					empty($rule['active'])
				&& $this->manual == 0
			) {
				throw new \Exception ('Rule '.$filter.' is inactive.');
			}		
			
			$this->ruleId = $rule['id'];		
			// We instance the rule
			$param['ruleId'] = $this->ruleId;
			$param['jobId'] = $this->id;
			$param['limit'] = $this->limit;
			$param['manual'] = $this->manual;		
			$this->rule = new rule($this->logger, $this->container, $this->connection, $param);
			if ($this->rule->isChild()) {
				throw new \Exception ('Rule '.$filter.' is a child rule. Child rules can only be run by the parent rule.');
			}
			return true;
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			$this->message .= $e->getMessage();
			return false;
		}	
	}

	// Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
	public function createDocuments() {		
		if ($this->limit > 0) {
			$createDocuments = $this->rule->createDocuments();
			if (!empty($createDocuments['error'])) {
				$this->message .= print_r($createDocuments['error'],true);
			}
			if (!empty($createDocuments['count'])) {
				$this->limit = $this->limit-$createDocuments['count'];
				if ($this->limit < 0) {
					$this->limit = 0;
				}
				return $createDocuments['count'];
			}
			else {
				return 0;
			}
		}
		else {
			return 0;
		}
	}
	
	// Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
	public function ckeckPredecessorDocuments() {
		$this->rule->ckeckPredecessorDocuments();
	}
	
	// Permet de filtrer les documents en fonction des filtres de la règle
	public function filterDocuments() {
		$this->rule->filterDocuments();
	}
	
	// Permet de contrôler si un docuement a une relation mais n'a pas de correspondance d'ID pour cette relation dans Myddleware
	public function ckeckParentDocuments() {
		$this->rule->ckeckParentDocuments();
	}
	
	// Permet de trasformer les documents
	public function transformDocuments() {
		$this->rule->transformDocuments();
	}
	
	// Permet de récupérer les données de la cible avant modification des données
	// 2 cas de figure : 
	//     - Le document est un document de modification
	//     - Le document est un document de création mais la règle a un paramètre de vérification des données pour ne pas créer de doublon
	public function getTargetDataDocuments() {
		$this->rule->getTargetDataDocuments();
	}

	// Ecriture dans le système source et mise à jour de la table document
	public function sendDocuments() {
		$sendDocuments = $this->rule->sendDocuments();	
		if (!empty($sendDocuments['error'])) {
			$this->message .= $sendDocuments['error'];
		}
	}
	
	// Ecriture dans le système source et mise à jour de la table document
	public function runError($limit, $attempt) {
		try {
			// Récupération de tous les flux en erreur ou des flux en attente (new) qui ne sont pas sur règles actives (règle child pour des règles groupées)
			$sqlParams = "	SELECT * 
							FROM Document	
							WHERE 
									(
											global_status = 'Error'
										OR status = 'New'
									)
								AND attempt <= :attempt 
							ORDER BY attempt ASC, source_date_modified ASC	
							LIMIT $limit";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue("attempt", $attempt);
		    $stmt->execute();	   				
			$documentsError = $stmt->fetchAll();
			if(!empty($documentsError)) {
				include_once 'rule.php';		
				foreach ($documentsError as $documentError) {
					$param['ruleId'] = $documentError['rule_id'];
					$param['jobId'] = $this->id;					
					$rule = new rule($this->logger, $this->container, $this->connection, $param);
					$errorActionDocument = $rule->actionDocument($documentError['id'],'rerun');
					if (!empty($errorActionDocument)) {
						$this->message .= print_r($errorActionDocument,true);
					}
				}			
			}			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}
	}
	
	// Fonction permettant d'initialiser le job
	public function initJob($paramJob) {	
		$this->paramJob = $paramJob;
		$this->id = uniqid('', true);
		$this->start = microtime(true);
		
		// Check if a job is already running
		$sqlJobOpen = "SELECT * FROM Job WHERE status = 'Start' LIMIT 1";
		$stmt = $this->connection->prepare($sqlJobOpen);
		$stmt->execute();	    
		$job = $stmt->fetch(); // 1 row
		// Error if one job is still running
		if (!empty($job)) {
			$this->message .= $this->tools->getTranslation(array('messages', 'rule', 'another_task_running')).';'.$job['id'];
			return false;
		}
		// Create Job
		$insertJob = $this->insertJob();
		if ($insertJob) {
			$this->createdJob = true;
			return true;
		}
		else {
			$this->message .=  'Failed to create the Job in the database';		
			return false;
		}
	}
	
	// Permet de clôturer un job
	public function closeJob() {
		// Get job data
		$this->logData = $this->getLogData();

		// Update table job
		return $this->updateJob();
	}
	
	
	// Permet d'exécuter des jobs manuellement depuis Myddleware
	public function actionMassTransfer($event,$param) {
		if (in_array($event, array('rerun','cancel'))) { 
			// Pour ces 2 actions, l'event est le premier paramètre et ce sont les ids des cocuments qui sont envoyés dans le $param
			$paramJob[] = $event;
			$paramJob[] = implode(',',$param);
			return $this->runBackgroundJob('massaction',$paramJob);
		}
		else {
			return 'Action '.$event.' unknown. Failed to run this action. ';
		}
	}
	
	// Myddleware upgrade
	public function myddlewareUpgrade() {
		try{
			// Read the file parameters.yml.dist with the new version of Myddleware
			$myddlewareNewParameters = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml.dist'));	
			if (!empty($myddlewareNewParameters['parameters']['myd_version'])) {
				// Read the file parameters.yml and change the version of Myddleware
				$myddlewareParameters = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml'));
				$myddlewareParameters['parameters']['myd_version'] = $myddlewareNewParameters['parameters']['myd_version'];
				$new_yaml = \Symfony\Component\Yaml\Yaml::dump($myddlewareParameters, 4);
				file_put_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml', $new_yaml);
			} else {
				throw new \Exception ('No version in the file parameters.yml.dist. Failed to update the version of Myddleware.');
			}
		} catch (\Exception $e) {
			$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->logger->error( $this->message );
		}	
	}
	
	// Lancement d'un job manuellement en arrière plan 
	protected function runBackgroundJob($job,$param) {
		try{
			// Création d'un fichier temporaire
			$guid = uniqid();
			
			// Formatage des paramètres
			$params = implode(' ',$param);
			
			// récupération de l'exécutable PHP, par défaut c'est php
			$php = $this->container->getParameter('php');
			if (empty($php['executable'])) {
				$php['executable'] = 'php';
			}
				
			//Create your own folder in the cache directory
			$fileTmp = $this->container->getParameter('kernel.cache_dir') . '/myddleware/job/'.$guid.'.txt';		
			$fs = new Filesystem();
			try {
				$fs->mkdir(dirname($fileTmp));
			} catch (IOException $e) {
				throw new \Exception ("An error occured while creating your directory");
			}
			exec($php['executable'].' '.__DIR__.'/../../../../app/console myddleware:'.$job.' '.$params.' --env='.$this->container->get( 'kernel' )->getEnvironment().'  > '.$fileTmp.' &', $output);
			$cpt = 0;
			// Boucle tant que le fichier n'existe pas
			while (!file_exists($fileTmp)) {
				if($cpt >= 29) {
					throw new \Exception ('Failed to run the job.');
				}
				sleep(1);
				$cpt++;
			}
			
			// Boucle tant que l id du job n'est pas dans le fichier (écris en premier)
			$file = fopen($fileTmp, 'r');
			$idJob = fread($file, 23);
			fclose($file);
			while (empty($idJob)) {
				if($cpt >= 29) {
					throw new \Exception ('No task id given.');
				}
				sleep(1);
				$file = fopen($fileTmp, 'r');
				$idJob = fread($file, 23);
				fclose($file);
				$cpt++;
			}
			// Renvoie du message en session
			$session = new Session();
			$session->set( 'info', array('<a href="'.$this->container->get('router')->generate('task_view', array('id'=>$idJob)).'">'.$this->container->get('translator')->trans('session.task.msglink').'</a>. '.$this->container->get('translator')->trans('session.task.msginfo')));
			return $idJob;
		} catch (\Exception $e) {
			$session = new Session();
			$session->set( 'info', array($e->getMessage())); // Vous venez de lancer une nouvelle longue tâche. Elle est en cours de traitement.
			return false;
		}
	}

	// Fonction permettant d'annuler massivement des documents
	public function massAction($action,$idsDoc) {
		if (empty($idsDoc)) {
			$this->message .=  'No Ids in parameters of the job massAction.';		
			return false;
		}
		
		try {
			// Formatage du tableau d'idate
			$idsDocArray = explode(',',$idsDoc);	
			$queryIn = '(';
			foreach ($idsDocArray as $idDoc) {
				$queryIn .= "'".$idDoc."',";
			}
			$queryIn = rtrim($queryIn,',');
			$queryIn .= ')';
			
			// Création de la requête
			$sqlParams = "	SELECT 
								Document.id,
								Document.rule_id
							FROM Document	
								INNER JOIN Rule
									ON Document.rule_id = Rule.id
							WHERE
									Document.global_status IN ('Open','Error')
								AND Document.id IN $queryIn
							ORDER BY Rule.id";
			$stmt = $this->connection->prepare($sqlParams);
		    $stmt->execute();	   				
			$documents = $stmt->fetchAll();

			if(!empty($documents)) {
				include_once 'rule.php';	
				$param['ruleId'] = '';
				foreach ($documents as $document) {
					// Chargement d'une nouvelle règle que si nécessaire
					if ($param['ruleId'] != $document['rule_id']) {
						$param['ruleId'] = $document['rule_id'];
						$param['jobId'] = $this->id;						
						$rule = new rule($this->logger, $this->container, $this->connection, $param);
					}
					$errorActionDocument = $rule->actionDocument($document['id'],$action);
					if (!empty($errorActionDocument)) {
						$this->message .= print_r($errorActionDocument,true);
					}
				}			
			}	
			else {
				$this->message .=  'No Document Open or in Error in parameters of the job massAction.';		
				return false;
			}
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
		}
	}
	
	public function getRules() {
		try {
			$sqlParams = "	SELECT name_slug 
							FROM RuleOrder
								INNER JOIN Rule
									ON Rule.id = RuleOrder.rule_id
							WHERE 
									Rule.active = 1
								AND	Rule.deleted = 0
							ORDER BY RuleOrder.order ASC";
			$stmt = $this->connection->prepare($sqlParams);
		    $stmt->execute();	   				
			$rules = $stmt->fetchAll();
			if(!empty($rules)) {	
				foreach ($rules as $rule) {
					$ruleOrder[] = $rule['name_slug'];
				}			
			}
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			return false;
		}
		if (empty($ruleOrder)) {
			return null;
		}
		return $ruleOrder;
	}
	
	// Fonction permettant de définir un ordre dans le lancement des règles
	public function orderRules() {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		 try {
			// Récupération de toutes les règles avec leurs règles liées (si plusieurs elles sont toutes au même endroit)
			// Si la règle n'a pas de relation on initialise l'ordre à 1 sinon on met 99
			$sql = "SELECT
						Rule.id,
						GROUP_CONCAT(RuleRelationShip.field_id SEPARATOR ';') field_id,
						IF(RuleRelationShip.field_id IS NULL, '1', '99') rule_order
					FROM Rule
						LEFT OUTER JOIN RuleRelationShip
							ON Rule.id = RuleRelationShip.rule_id
					WHERE
						Rule.deleted = 0
					GROUP BY Rule.id";
			$stmt = $this->connection->prepare($sql);
			$stmt->execute();	    
			$rules = $stmt->fetchAll(); 	
			if (!empty($rules)) {
				// Création d'un tableau en clé valeur et sauvegarde d'un tableau de référence
				$ruleKeyVakue = array();
				foreach ($rules as $rule) {
					$ruleKeyVakue[$rule['id']] = $rule['rule_order'];
					$rulesRef[$rule['id']] = $rule;
				}	
				
				// On calcule les priorité tant que l'on a encore des priorité 99
				// On fait une condition sur le $i pour éviter une boucle infinie
				$i = 0;
				while ($i < 20 && array_search('99', $ruleKeyVakue)!==false) {
					$i++;
					// Boucles sur les régles
					foreach ($rules as $rule) {
						$order = 0;
						// Si on est une règle sans ordre
						if($rule['rule_order'] == '99') {
							// Récupération des règles liées et recherche dans le tableau keyValue
							$rulesLink = explode(";", $rule['field_id']);
							foreach ($rulesLink as $ruleLink) {
								if(
										!empty($ruleKeyVakue[$ruleLink])
									&&	$ruleKeyVakue[$ruleLink] > $order
								) {
									$order = $ruleKeyVakue[$ruleLink];
								}
							}
							// Si toutes les règles trouvées ont une priorité autre que 99 alors on affecte à la règle la piorité +1 dans les tableaux de références
							if ($order < 99) {
								$ruleKeyVakue[$rule['id']] = $order+1;
								$rulesRef[$rule['id']]['rule_order'] = $order+1;
							}
						}
					}	
					$rules = $rulesRef;		
				}
				
				// On vide la table RuleOrder
				$sql = "DELETE FROM RuleOrder";
				$stmt = $this->connection->prepare($sql);
				$stmt->execute();	
				
				//Mise à jour de la table
				$insert = "INSERT INTO RuleOrder VALUES ";
				foreach ($ruleKeyVakue as $key => $value) {
					$insert .= "('$key','$value'),";
				}
				// Suppression de la dernière virgule  
				$insert = rtrim($insert,','); 
				$stmt = $this->connection->prepare($insert);
				$stmt->execute();		
			} 
			$this->connection->commit(); // -- COMMIT TRANSACTION
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->message .= 'Failed to update table RuleOrder : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->logger->error($this->message);
			return false;
		}	 
	}
	
	public function generateTemplate($nomTemplate,$descriptionTemplate,$rulesId) {
		include_once 'template.php';
		try {
			// Init array
			$templateArray = array(
								'name' => $nomTemplate,
								'description' => $descriptionTemplate
							);
			if (!empty($rulesId)) {
				$template = new template($this->logger, $this->container, $this->connection);
				$rulesOrderIds = $template->setRules($rulesId);

				foreach($rulesOrderIds as $rulesOrderId) {	
					// Generate array with all rules parameters
					$templateArray['rules'][] = $template->extractRule($rulesOrderId['rule_id']);
				}
				// Ecriture du fichier
				$yaml = \Symfony\Component\Yaml\Yaml::dump($templateArray, 4);
				file_put_contents($this->container->getParameter('kernel.root_dir').'/../src/Myddleware/RegleBundle/Templates/'.$nomTemplate.'.yml', $yaml);
			}
		} catch (\Exception $e) {
			$this->message .= 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$this->logger->error($this->message);
			return false;
		}	
		return true;
	}
	
	// Permet d'indiquer que le job est lancé manuellement
	protected function setManual() {
		if ($this->env == 'background') {
			$this->manual = 0;
		}
		else {
			$this->manual = 1;
		}
	}
	
	// Permet d'indiquer que le job est lancé manuellement
	public function setConfigValue($name,$value) {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION suspend auto-commit
		// Récupération de la valeur de la config
		$select = "	SELECT * FROM Config WHERE conf_name = '$name'";
		$stmt = $this->connection->prepare($select);
		$stmt->execute();	   				
		$config = $stmt->fetch();
		try {
			// S'il n'existe pas on fait un INSERT sinon un UPDATE
			if (empty($config)) {
				$sqlParams = "INSERT INTO Config (conf_name, conf_value) VALUES (:name, :value)";
			}
			else {
				$sqlParams = "UPDATE Config SET conf_value = :value WHERE conf_name = :name";
			}
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue("value", $value);
			$stmt->bindValue("name", $name);
			$stmt->execute();	
			$this->connection->commit(); // -- COMMIT TRANSACTION
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->logger->error( 'Failed to update the config name '.$name.' whithe the value '.$value.' : '.$e->getMessage() );
			echo 'Failed to update the config name '.$name.' whithe the value '.$value.' : '.$e->getMessage() ;
			return false;
		}		
		return true;
	}
	
	// Permet d'indiquer que le job est lancé manuellement
	public function getConfigValue($name) {
		// Récupération de la valeur de la config
		$select = "	SELECT * FROM Config WHERE conf_name = '$name'";
		$stmt = $this->connection->prepare($select);
		$stmt->execute();	   				
		$config = $stmt->fetch();
		return $config['conf_value'];
	}


	// Send notification to receive statistique about myddleware data transfer
	public function sendNotification() {
		try {
			// Get the email address for notification
			$contactMail = $this->container->getParameter('notification_emailaddress');
			if (empty($contactMail)) {
				throw new \Exception ('No email address for notification. Please add the parameter notification_emailaddress in the file app/config/config_background.yml.');
			}
			
			// Write the introduction
			$textMail = $this->tools->getTranslation(array('email_notification', 'hello')).chr(10).chr(10).$this->tools->getTranslation(array('email_notification', 'introduction')).chr(10);

			// Récupération du nombre de données transférées depuis la dernière notification. On en compte qu'une fois les erreurs
			$sqlParams = "	SELECT
								count(distinct Log.doc_id) cpt,
								Document.global_status
							FROM Job
								INNER JOIN Log
									ON Log.job_id = Job.id
								INNER JOIN Rule
									ON Log.rule_id = Rule.id
								INNER JOIN Document
									ON Document.id = Log.doc_id
							WHERE
									Job.begin BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
								AND (
										Document.global_status != 'Error'
									OR (
											Document.global_status = 'Error'
										AND Document.date_modified BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
									)
								)
							GROUP BY Document.global_status";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->execute();	   				
			$cptLogs = $stmt->fetchAll();
			$job_open = 0;
			$job_close = 0;
			$job_cancel = 0;
			$job_error = 0;
			if (!empty($cptLogs)) {
				foreach ($cptLogs as $cptLog) {
					switch ($cptLog['global_status']) {
						case 'Open':
							$job_open = $cptLog['cpt'];
							break;
						case 'Error':
							$job_error = $cptLog['cpt'];
							break;
						case 'Close':
							$job_close = $cptLog['cpt'];
							break;
						case 'Cancel':
							$job_cancel = $cptLog['cpt'];
							break;
					}
				}
			}			
			$textMail .= $this->tools->getTranslation(array('email_notification', 'transfer_success')).' '.$job_close.chr(10);
			$textMail .= $this->tools->getTranslation(array('email_notification', 'transfer_error')).' '.$job_error.chr(10);
			$textMail .= $this->tools->getTranslation(array('email_notification', 'transfer_open')).' '.$job_open.chr(10);	
			
			// Récupération des règles actives
			$sqlParams = "	SELECT * 
							FROM Rule
							WHERE
									Rule.active = 1
								AND	Rule.deleted = 0
			";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->execute();	   				
			$activeRules = $stmt->fetchAll();
			if (!empty($activeRules)) {
				$textMail .= chr(10).$this->tools->getTranslation(array('email_notification', 'active_rule')).chr(10);
				foreach ($activeRules as $activeRule) {
					$textMail .= " - ".$activeRule['name']." v".$activeRule['version'].chr(10);
				}
			}
			else {
				$textMail .= chr(10).$this->tools->getTranslation(array('email_notification', 'no_active_rule')).chr(10);
			}
			
			
			// Get errors since the last notification
			if ($job_error > 0) {
				$sqlParams = "	SELECT
									Log.created,
									Log.msg,
									Log.doc_id,
									Rule.name
								FROM Job
									INNER JOIN Log
										ON Log.job_id = Job.id
									INNER JOIN Rule
										ON Log.rule_id = Rule.id
									INNER JOIN Document
										ON Document.id = Log.doc_id
								WHERE
										Job.begin BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
									AND Document.date_modified BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
									AND Document.global_status = 'Error'
								ORDER BY Log.created ASC
								LIMIT 100	";
				$stmt = $this->connection->prepare($sqlParams);
				$stmt->execute();	   				
				$logs = $stmt->fetchAll();

				if (count($logs) == 100) {
					$textMail .= chr(10).chr(10).$this->tools->getTranslation(array('email_notification', '100_first_erros')).chr(10);
				}
				else  {
					$textMail .= chr(10).chr(10).$this->tools->getTranslation(array('email_notification', 'error_list')).chr(10);
				}
				foreach ($logs as $log) {
					$textMail .= " - Règle $log[name], id transfert $log[doc_id], le $log[created] : $log[msg]".chr(10);
				}
			}
			
			$textMail .= chr(10).$this->tools->getTranslation(array('email_notification', 'best_regards')).chr(10).$this->tools->getTranslation(array('email_notification', 'signature'));
			$message = \Swift_Message::newInstance()
				->setSubject($this->tools->getTranslation(array('email_notification', 'subject')))
 				->setFrom('no-reply@myddleware.com')
				->setTo($contactMail)
				->setBody($textMail)
			;
			$send = $this->container->get('mailer')->send($message);
			if (!$send) {
				$this->logger->error('Failed to send email : '.$textMail.' to '.$contactMail);	
				throw new \Exception ('Failed to send email : '.$textMail.' to '.$contactMail);
			}
			return true;
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			$this->message .= $e->getMessage();
			return false;							
		}
	}
	
	// Permet de supprimer toutes les données des tabe source, target et history en fonction des paramètre de chaque règle
	public function clearData() {
		//Get the table list 
		// $sqlTables = "SHOW TABLES";
		// $stmt = $this->connection->prepare($sqlTables);
		// $stmt->execute();	   				
		// $tablesQuery = $stmt->fetchAll();
		// foreach ($tablesQuery as $key => $table) {
			// $tables[] = current($table);
		// }
	
		// Récupération de chaque règle et du paramètre de temps de suppression
		$sqlParams = "	SELECT 
							Rule.id,
							Rule.name_slug,
							RuleParam.value days
						FROM Rule
							INNER JOIN RuleParam
								ON Rule.id = RuleParam.rule_id
						WHERE
							RuleParam.name = 'delete'";
		$stmt = $this->connection->prepare($sqlParams);
		$stmt->execute();	   				
		$rules = $stmt->fetchAll();	
		if (!empty($rules)) {
			$this->connection->beginTransaction();						
			try {
				// Boucle sur toutes les règles
				foreach ($rules as $rule) {	
					// Delete document data
					$deleteSource = "
						DELETE DocumentData
						FROM Document
							INNER JOIN DocumentData
								ON Document.id = DocumentData.doc_id
						WHERE 
								Document.rule_id = :ruleId
							AND Document.global_status IN ('Close','Cancel')
							AND DATEDIFF(CURRENT_DATE( ),Document.date_modified) >= :days
					";							
					$stmt = $this->connection->prepare($deleteSource);
					$stmt->bindValue("ruleId", $rule['id']);
					$stmt->bindValue("days", $rule['days']);
					$stmt->execute();

					// Delete log for these rule	
					$deleteLog = "
						DELETE Log
						FROM Log
							INNER JOIN Document
								ON Log.doc_id = Document.id
						WHERE 
								Log.rule_id = :ruleId
							AND Log.msg IN ('Status : Filter_OK','Status : Predecessor_OK','Status : Relate_OK','Status : Transformed','Status : Ready_to_send')	
							AND Document.global_status IN ('Close','Cancel')
							AND DATEDIFF(CURRENT_DATE( ),Document.date_modified) >= :days
					";						
					$stmt = $this->connection->prepare($deleteLog);
					$stmt->bindValue("ruleId", $rule['id']);
					$stmt->bindValue("days", $rule['days']);
					$stmt->execute(); 
					
				}
				// Suppression des jobs de transfert vide et des autres jobs qui datent de plus de nbDayClearJob jours
				$deleteJob = " 	
					DELETE 
					FROM Job
					WHERE 
							status = 'End'
						AND (
								(
										param NOT IN ('cleardata', 'backup', 'notification')
									AND message IN ('', 'Another job is running. Failed to start job. ')
									AND open = 0
									AND close = 0
									AND cancel = 0
									AND error = 0
								)
							OR 	(
									param IN ('cleardata', 'backup', 'notification')
									AND message = ''
									AND DATEDIFF(CURRENT_DATE( ),end) > :nbDayClearJob
								)
							)
				";	
				$stmt = $this->connection->prepare($deleteJob);
				$stmt->bindValue("nbDayClearJob", $this->nbDayClearJob);
				$stmt->execute();
				
				$this->connection->commit(); // -- COMMIT TRANSACTION
			} catch (\Exception $e) {
				$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
				$this->message .= 'Failed to clear logs and the documents data: '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$this->logger->error($this->message);	
			}	
		}
	}
	
	
 	// Récupération des données du job
	protected function getLogData() {
		try {
			// Récupération du nombre de document envoyé et en erreur pour ce job
			$this->logData['Close'] = 0;
			$this->logData['Cancel'] = 0;
			$this->logData['Open'] = 0;
			$this->logData['Error'] = 0;
			$this->logData['paramJob'] = $this->paramJob;
			$sqlParams = "	SELECT 
								count(distinct Document.id) nb,
								Document.global_status
							FROM Log
								INNER JOIN Document
									ON Log.doc_id = Document.id
							WHERE
								Log.job_id = :id
							GROUP BY Document.global_status";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue("id", $this->id);
		    $stmt->execute();	   				
			$data = $stmt->fetchAll();
			if(!empty($data)) {
				foreach ($data as $row) {
					if($row['global_status'] == 'Close' ) {
						$this->logData['Close'] = $row['nb'];
					}
					elseif($row['global_status'] == 'Error' ) {
						$this->logData['Error'] = $row['nb'];	
					}
					elseif($row['global_status'] == 'Cancel' ) {
						$this->logData['Cancel'] = $row['nb'];	
					}
					elseif($row['global_status'] == 'Open' ) {
						$this->logData['Open'] = $row['nb'];	
					}
				}			
			}	
			
			// Récupération des solutions du job
			$sqlParams = "	SELECT 
								Connector_target.sol_id sol_id_target,
								Connector_source.sol_id sol_id_source
							FROM (SELECT DISTINCT rule_id FROM Log WHERE job_id = :id) rule_job
								INNER JOIN Rule
									ON rule_job.rule_id = Rule.id
								INNER JOIN Connector Connector_source
									ON Connector_source.id = Rule.conn_id_source
								INNER JOIN Connector Connector_target
									ON Connector_target.id = Rule.conn_id_target";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue("id", $this->id);
		    $stmt->execute();	   				
			$solutions = $stmt->fetchAll();
			$this->logData['solutions'] = '';
			if (!empty($solutions)) {
				foreach ($solutions as $solution) {
					$concatSolution[] = $solution['sol_id_target'];
					$concatSolution[] = $solution['sol_id_source'];
				}
				$concatSolutions = array_unique($concatSolution);
				// Mise au format pour la liste multi de Sugar
				$concatSolutions = '^'.implode("^,^", $concatSolutions).'^';
				$this->logData['solutions'] = $concatSolutions;
			}
			
			// Récupération de la durée du job
			$time_end = microtime(true);
			$this->logData['duration'] = round($time_end - $this->start,2);
			
			// récupération de l'id du job
			$this->logData['myddlewareId'] = $this->id;
					
			// Indique si le job est lancé manuellement ou non
			$this->logData['Manual'] = $this->manual;
			
			// Récupération des erreurs
			$this->logData['jobError'] = $this->message;
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			$this->logData['jobError'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
		}
		return $this->logData;
	}
	
	// Mise à jour de la table Job
	protected function updateJob() {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			$close = $this->logData['Close'];
			$cancel = $this->logData['Cancel'];
			$open = $this->logData['Open'];
			$error = $this->logData['Error'];
			$now = gmdate('Y-m-d H:i:s');
			$message = $this->message;
			if (!empty($this->message)) {
				$message = htmlspecialchars($this->message);
			}
			$query_header = "UPDATE Job 
							SET 
								end = :now, 
								status = 'End', 
								close = :close, 
								cancel = :cancel, 
								open = :open, 
								error = :error, 
								message = :message
							WHERE id = :id"; 	
			$stmt = $this->connection->prepare($query_header);
			$stmt->bindValue("now", $now);
			$stmt->bindValue("close", $close);
			$stmt->bindValue("cancel", $cancel);
			$stmt->bindValue("open", $open);
			$stmt->bindValue("error", $error);
			$stmt->bindValue("message", $message);
			$stmt->bindValue("id", $this->id);
			$stmt->execute();
			$this->connection->commit(); // -- COMMIT TRANSACTION			
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->logger->error( 'Failed to update Job : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			$this->message .= 'Failed to update Job : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';		
			return false;
		}
		return true;
	}
	
	protected function insertJob() {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			$now = gmdate('Y-m-d H:i:s');
			$query_header = "INSERT INTO Job (id, begin, status, param, manual) VALUES ('$this->id', '$now', 'Start', '$this->paramJob', '$this->manual')";
			$stmt = $this->connection->prepare($query_header);
			$stmt->execute();
			$this->connection->commit(); // -- COMMIT TRANSACTION
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			$this->logger->error( 'Failed to create Job : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			$this->message .=  'Failed to create Job : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';		
			return false;
		}
		return true;
	}
	
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/job.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class job extends jobcore {
		
	}
}
?>