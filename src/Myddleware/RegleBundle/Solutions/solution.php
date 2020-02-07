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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Bridge\Monolog\Logger as Logger;
use Doctrine\DBAL\Connection as Connection; // Connexion BDD
use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Accède aux services
//use Psr\LoggerInterface;
use Myddleware\RegleBundle\Classes\document as documentMyddleware; // document Myddleware

class solutioncore {

	// Permet d'indiquer que la connexion webservice est valide
	public $connexion_valide=false;
	
	public $js = 0;
	public $refresh_token = false;
	public $callback = false;
	
	// Session de la connexion webservice 
	protected $session;
	
	// Liste des champs d'un module
	protected $moduleFields = array();
	protected $fieldsRelate = array();
	
	// Permet d'ajouter des champs nécessaires lorsque l'on va lire les données dans la solution source 
	// Tableau de type array('id','date_modified')
	protected $required_fields = array();
	
	// URL de la solution pour atteindre les webservices
	protected $paramConnexion;
	
	// Classe permettant d'enregistrer les log Symfony
	protected $logger;
	
	// Classe permettant la connexion à la base données
	protected $conn;	
	
	// Classe permettant la connexion à la base données
	protected $container;	
	
	// Tableau comportant les différents types de BDD valides
	protected $type_valide = array('text');
	
	// Liste des modules à exclure pour chaque solution
	protected $exclude_module_list = array(
										'default' => array(),
										'target' => array(),
										'source' => array()
									);

	// Liste des champs à exclure pour chaque solution
	protected $exclude_field_list = array();
	
	// Module list that allows to make parent relationships
	protected $allowParentRelationship = array();
	
	// Enable the read record button on the data transfer detail view for the source solution
	protected $readRecord = true;		
	
	// Instanciation de la classe de génération de log Symfony
    public function __construct(Logger $logger, Container $container, Connection $dbalConnection) {
    	$this->logger = $logger;
		$this->conn = $dbalConnection;
		$this->container = $container;
	}

	// Fonction permettant de se loguer à la solution
	// Param est un tableau contenant tous les paramètres nécessaires à la connexion
	// Cette méthode doit mettre à jour les attributs : 
		// $this->session avec la session de la solution
		// $this->connexion_valide (true si la connexion estréussie, false sinon)
	public function login($paramConnexion) {	
		// Instanciate object to decrypte data
		$encrypter = new \Illuminate\Encryption\Encrypter(substr($this->container->getParameter('secret'),-16));
		// Decrypt connexion parameters
		foreach ($paramConnexion as $key => $value) {				
			if(is_string($value)) {
				try {
					$paramConnexion[$key] = $encrypter->decrypt($value);
				} catch (\Exception $e) { // No error if decrypt failed because some data aren't crypted (eg reference date)
				}
			}
		}
		$this->paramConnexion = $paramConnexion;
    }
	
	public function logout() {	
		return true;
    }
	
	// Permet de récupérer la classe de génération de log Symfony
	protected function getLogger() {		
		return $this->logger;
	}

	// Permet de se connecter à la base de données
	protected function getConn() {		
		return $this->conn;
	}
	
	// Permet de mettre à jour le statut d'un document après création ou modification dans la cible
	protected function updateDocumentStatus($idDoc,$value,$param,$forceStatus = null) {
		$this->conn->beginTransaction();	
		try {	
			$param['id_doc_myddleware'] = $idDoc;
			$document = new documentMyddleware($this->logger, $this->container, $this->conn, $param);
			//  Si on a un message on l'ajoute au document
			if (!empty($value['error'])) {
				$document->setMessage($value['error']);
			}
			// Mise à jour de la table document avec l'id target comme id de document
			// Si la création a fonctionné	
			if ($value['id'] != '-1') {
				if (empty($forceStatus)) {
					$status = 'Send';
				}
				else {
					$status = $forceStatus;
				}
				// In cas of a child document, it is possible to have $value['id'] empty, we just set an error because the document can't be sent again (parent document successfully sent)
				if (!empty($value['id'])) {
					$document->updateTargetId($value['id']);	
				}
				else {
					$document->setMessage('No target ID found in return of the parent document creation. ');
				}
				$document->updateStatus($status);
				$response[$idDoc] = true;							
			}
			else {
				if (empty($forceStatus)) {
					$status = 'Error_sending';
				}
				else {
					$status = $forceStatus;
				}
				$document->setMessage('Failed to send document. ');
				$document->setTypeError('E');
				$document->updateStatus($status);
				$response[$idDoc] = false;
			}
			$this->conn->commit(); // -- COMMIT TRANSACTION
		}
		catch (\Exception $e) {
			$this->conn->rollBack(); // -- ROLLBACK TRANSACTION
			$document->setMessage('Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
			$document->setTypeError('E');
			$document->updateStatus('Error_sending : '.$e->getMessage());
			$this->logger->error( 'Failed to send document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
			$response[$idDoc] = false;
		}				
		return $response;
	}
	
	// Cette méthode renvoie un tableau permettant d'indiquer tous les champs nécessaire à la connexion (login, mot de passe...)
	// Exemple de tableau
	// array(
					// array(
							// 'name' => 'login',
							// 'type' => 'text',
							// 'label' => 'solution.fields.login'
						// ),
					// array(
							// 'name' => 'password',
							// 'type' => 'password',
							// 'label' => 'solution.fields.password'
						// )
		// );
	public function getFieldsLogin() {	
	}
	
	// Même structure que la méthode getFieldsLogin
	// Prend en paramètre d'entre source ou target
	public function getFieldsParamUpd($type,$module) {
		return array();	
	}
	
	// Renvoie la liste des champs sur lequel on peut vérifier les doublons
	public function getFieldsDuplicate($module) {	
		if(isset($this->FieldsDuplicate[$module])){
			return $this->FieldsDuplicate[$module];
		}
		elseif(isset($this->FieldsDuplicate['default'])){
			return $this->FieldsDuplicate['default'];
		}
		else {
			return false;
		}
	}
	
	
	// Méthode permettant de récupérer le nom de tous les modules accessible à l'utilisateur
	// Tableau sous la forme : 
	// array(
		// nom_module1 => libellé module 1,
		// nom_module2 => libellé module 2
	// )
	// Renvoie false si aucun module n'a été récupéré
	public function get_modules($type = 'source') {
	}
	
	// Cette méthode doit remplir les attributs : 
		// moduleFields avec le tableu ci-dessus
		// fieldsRelate avec la même structure que moduleFields mais ne contenant que les champs de type relation
	public function get_module_fields($module, $type = 'source') {
		$this->moduleFields = array();
		$this->fieldsRelate = array();
		// The field Myddleware_element_id is ID of the current module. It is always added for the field mapping 
		$this->moduleFields['Myddleware_element_id'] = array(
												'label' => 'ID '.$module,
												'type' => 'varchar(255)',
												'type_bdd' => 'varchar(255)',
												'required' => 0
											);																
		return $this->moduleFields;
	}

	
	
	// Méthode permettant de renvoyer l'attribut fieldsRelate
	public function get_module_fields_relate($module) {
		if(isset($module))
			$this->addRequiredRelationship($module);
		return $this->fieldsRelate;
	}
	
	// Permet d'ajouter des règles en relation si les règles de gestion standard ne le permettent pas
	// Par exemple si on veut connecter des règles de la solution SAP CRM avec la solution SAP qui sont 2 solutions différentes qui peuvent être connectées
	public function get_rule_custom_relationship($module,$type) {
		return null;
	}
	
	
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux ou pour réchercher un doublon dans la cible)
	// Param contient : 
	//	module : le module appelé 
	//	fields : les champs demandés sous forme de tableau, exemple : array('name','date_entered')
	//	query : les champs à rechercher, exemple : array('name'=>'mon_compte')
	// Valeur de sortie est un tableau contenant : 
	//		done : Le nombre d'enregistrement trouvé
	//   	values : les enregsitrements du module demandé (l'id' est obligatoires), exemple Array(['id] => 454664654654, ['name] => dernier)
	public function read_last($param) {
		$result['done'] = false;					
		return $result;
	}
	
	
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	// Param contient : 
	//	date_ref : la date de référence à partir de laquelle on récupère les enregistrements, format bdd AAAA-MM-JJ hh:mm:ss
	//	module : le module appelé 
	//	fields : les champs demandés sous forme de tableau, exemple : array('name','date_entered')
	//	limit : la limite du nombre d'enregistrement récupéré (la limite par défaut étant 100)
	// Valeur de sortie est un tableau contenant : 
	//		count : Le nombre d'enregistrement trouvé
	//		date_ref : la nouvelle date de référence
	//   	values : les enregsitrements du module demandé (l'id et la date de modification (libellés 'id' et 'date_modified') sont obligatoires), L'id est en clé du tableau de valeur pour chaque docuement
	// 			     exemple Array([454664654654] => array( ['name] => dernier,  [date_modified] => 2013-10-11 18:41:18))
	// 				 Values peut contenir le tableau ZmydMessage contenant un table de message array (type => 'E', 'message' => 'erreur lors....')
	public function read($param) {
	}
	
	
	
	// Permet de créer un enregistrement
	// $param contient  : 
	//  -> le module destinataire
	//  -> les données à envoyer sour cette forme : 
	// Array
		// (
			// [0] => Array
				// (
					// [id_sfaure01_001_target] => 52e58c482b704
					// [name] => myddl01
					// [email1] => myddle01@test.test
				// )
			// [1] => Array
				// (
					// [id_sfaure01_001_target] => 52e58c482baaa
					// [name] => myddl02
					// [email1] => myddle02@test.test
				// )

		// )
	// Cette fonction retourne un tableau d'ID dans le même ordre que le tableau en entrée sous cette forme : 
	// Array
        // (
            // [0] => e1843994-10b6-09da-b2ab-52e58f6f7e57
            // [1] => e3bc5d6a-f137-02ea-0f81-52e58fa5f75f
        // )
	public function create($param) {
	}
	
	
	
	// Permet de mettre à jour un enregistrement
	// Permet de créer un enregistrement
	// $param contient  : 
	//  -> le module destinataire
	//  -> les données à envoyer sour cette forme (le champ id_target est obligatoire) : 
	// Array
		// (
			// [0] => Array
				// (
					// [target_id] => 54545-sds542s1d-sd21s2d54
					// [id_sfaure01_001_target] => 52e58c482b704
					// [name] => myddl01
					// [email1] => myddle01@test.test
				// )
			// [1] => Array
				// (
					// [target_id] => 54545-sds542s1d-sd21s2d54
					// [id_sfaure01_001_target] => 52e58c482baaa
					// [name] => myddl02
					// [email1] => myddle02@test.test
				// )

		// )
	// Cette fonction retourne un tableau d'ID dans le même ordre que le tableau en entrée sous cette forme : 
	// Array
        // (
            // [0] => e1843994-10b6-09da-b2ab-52e58f6f7e57
            // [1] => e3bc5d6a-f137-02ea-0f81-52e58fa5f75f
        // )
	public function update($data) {
	
	}
	
	
	
	// Permet de supprimer un enregistrement
	public function delete($id) {
	
	}
	
	// Permet de renvoyer le mode de la règle en fonction du module target
	// Valeur par défaut "0"
	// Si la règle n'est qu'en création, pas en modicication alors le mode est C
	public function getRuleMode($module,$type) {
		return array(
				'0' => 'create_modify',
				'C' => 'create_only'
			);
	}
	
	public function setMessageCreateRule($module) {
	}
	
	// Permet d'ajouter des boutoon sur la page flux en fonction de la solution source ou targe
	// Type : source ou target
	public function getDocumentButton($type) {	
		return array();
	}
	
	// Permet d'indiquer le type de référence, si c'est une date (true) ou un texte libre (false)
	public function referenceIsDate($module) {
		return true;
	}
	
	// Permet de lancer l'action demandée dans la page flux
	public function documentAction($idDocument,$function) {
		return $this->$function($idDocument);
	}
	
	// Return if the read record button has to be display on the data transfert view
	public function getReadRecord() {
		return $this->readRecord;
	}

	// Permet de faire des contrôles dans Myddleware avant sauvegarde de la règle
	// Si le retour est false, alors la sauvegarde n'est pas effectuée et un message d'erreur est indiqué à l'utilisateur
	// data est de la forme : 
		// [datereference] => 2015-02-23 00:00:00 
		// [connector] => Array ( [source] => 3 [cible] => 30 ) 
		// [content] => Array ( 
			// [fields] => Array ( [name] => Array ( [Date] => Array ( [champs] => Array ( [0] => date_entered [1] => date_modified ) [formule] => Array ( [0] => {date_entered}.{date_modified} ) ) [account_Filter] => Array ( [champs] => Array ( [0] => name ) ) ) ) 
			// [params] => Array ( [mode] => 0 ) ) 
		// [relationships] => Array ( [0] => Array ( [target] => compte_Reference [rule] => 54ea64f1601fc [source] => Myddleware_element_id ) ) 
		// [module] => Array ( [source] => Array ( [solution] => sugarcrm [name] => Accounts ) [target] => Array ( [solution] => bittle [name] => oppt_multi7 ) ) 
	// La valeur de retour est de a forme : array('done'=>false, 'message'=>'message erreur');	ou array('done'=>true, 'message'=>'')
	// Le tableau de sortie peut aussi avoir une entrée params permettant d'indiquer l'ajout de paramètre à la règle
	public function beforeRuleSave($data,$type) {
		return array('done'=>true, 'message'=>'');
	}
	
	// Permet d'effectuer une action après la sauvegarde de la règle dans Myddleqare
	// Mêmes paramètres en entrée que pour la fonction beforeSave sauf que l'on a ajouté l'entrée ruleId au tableau
	// Retourne des message de type $messages[] = array ( 'type' => 'success', 'message' => 'OK');
	public function afterRuleSave($data,$type) {
		return array();
	}
	
	// Fonction permettant de faire l'appel REST
	protected function call($method, $parameters){	
    }

	// Permet d'ajouter les champs obligatoires dans la listes des champs pour la lecture dans le système cible 
	protected function addRequiredField($fields,$module = 'default') {
		// If no entry for the module we put default
		if (empty($this->required_fields[$module])) {
			$module = 'default';
		}
		
		// Check $fields variable
		if (empty($fields)) {
			$fields = array();
		}
		
		// Boucle sur tous les champs obligatoires
		if (!empty($this->required_fields[$module])) {
			foreach($this->required_fields[$module] as $required_field) {
				// Vérification de la présence du champs obligatoire
				$search_field = array_search($required_field, $fields);
				if ($search_field === false) {
					$fields[] = $required_field;
				}
			}
		}
		return $fields;			

	}
	
	// Permet d'ajouter les relations obligatoires dans la listes des relations 
	protected function addRequiredRelationship($module) {
		if(!isset($this->required_relationships[$module]))
			$this->required_relationships[$module] = array();
		// Boucle sur tous les champs obligatoires
		foreach($this->required_relationships[$module] as $required_relationship) {
			if (!in_array($required_relationship, array_keys($this->fieldsRelate))) {
				$this->fieldsRelate[$required_relationship] = array(
																'label' => $required_relationship,
																'type' => 'text',
																'type_bdd' => 'varchar(255)',
																'required' => false,
																'required_relationship' => 1
															);
			} else {
				$this->fieldsRelate[$required_relationship]['required_relationship'] = 1;
			}
		}
		
		// Ajout systématique de l'id du module en cours dans les relation disponible : utile lorsque 2 modules source (2 règles) viennent mettre à jour 1 règle target 
		if (empty($this->fieldsRelate['id'])) {
			$this->fieldsRelate['Myddleware_element_id'] = array(
																'label' => 'ID '.$module,
																'type' => 'varchar(255)',
																'type_bdd' => 'varchar(255)',
																'required' => false,
																'required_relationship' => 0
															);
		}
	}
	
	// Permet de supprimer l'élement Myddleware_element_id ajouter artificiellement dans un tableau de champ
	protected function cleanMyddlewareElementId($fieldArray) {
		if (!empty($fieldArray)) {
			$fieldArray = array_diff ( $fieldArray , array('Myddleware_element_id') , array('my_value') );
		}
		return $fieldArray;
	}
	
	// Function de conversion de date format solution à une date format Myddleware
	protected function dateToMyddleware($date) {
	}// dateToMyddleware ($date)
	
	// Function de conversion de datetime format solution à un datetime format Myddleware
	protected function dateTimeToMyddleware($dateTime) {
	}// dateTimeToMyddleware($dateTime)	
	
	// Function de conversion de date format Myddleware à une date format solution
	protected function dateFromMyddleware($date) {
	}// dateToMyddleware ($date)
	
	// Function de conversion de datetime format Myddleware à un datetime format solution
	protected function dateTimeFromMyddleware($dateTime) {
	}// dateTimeToMyddleware($dateTime)
	
	protected function getInfoDocument($idDocument) {
		$connection = $this->getConn();
		$sqlParams = "	SELECT *
						FROM Document 
							INNER JOIN Rule
								 ON Document.rule_id = Rule.id
								AND Document.deleted = 0
						WHERE id = :id_doc";								
		$stmt = $connection->prepare($sqlParams);
		$stmt->bindValue(":id_doc", $idDocument);
		$stmt->execute();	   				
		$documentData = $stmt->fetch();
		return $documentData;
	}
	
	// Permet de récupérer la source ID du document en paramètre
	protected function getSourceId($idDoc) {
		// Récupération du source_id
		$sql = "SELECT `source_id` FROM `Document` WHERE `id` = :idDoc AND Document.deleted = 0";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(":idDoc", $idDoc);
		$stmt->execute();
		$sourceId = $stmt->fetch();		
		return $sourceId['source_id'];
	}
	
	// Ajout de champ personnalisé dans la target ex : bittle 
	public function getFieldMappingAdd($moduleTarget) {
		return false;
	}
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
	public function getDateRefName($moduleSource, $RuleMode) {
		return null;
	}
	
	// The function return true if we can display the column parent in the rule view, relationship tab
	public function allowParentRelationship($module) {
		if (
				!empty($this->allowParentRelationship)
			 && in_array($module,$this->allowParentRelationship)
		) {
			return true;
		}
		return false;
	}
	
	// Check data before create 
	// Add a throw exeption if error
	protected function checkDataBeforeCreate($param,$data) {
		// Exception if the job has been stopped manually
		$this->isJobActive($param);
		return $data;
	}

	// Check data before update 
	// Add a throw exeption if error
	protected function checkDataBeforeUpdate($param,$data) {
		// Exception if the job has been stopped manually
		$this->isJobActive($param);
		return $data;
	}
	
	// Check if the job is still active
	protected function isJobActive($param) {
		$sqlJobDetail = "SELECT * FROM Job WHERE id = :jobId";
		$stmt = $this->conn->prepare($sqlJobDetail);
		$stmt->bindValue(":jobId", $param['jobId']);
		$stmt->execute();	    
		$job = $stmt->fetch(); // 1 row	
		if (
				empty($job['status'])
			|| 	$job['status'] != 'Start'
		) {
			throw new \Exception('The task has been manually stopped. ');
		}
	}
	
	// Permet de récupérer les paramètre de login afin de faire un login quand on ne vient pas de la classe rule	
	protected function getParamLogin($connId) {
		// RECUPERE LE NOM DE LA SOLUTION			
		$sql = "SELECT Solution.name  
				FROM Connector
					INNER JOIN Solution 
						ON Solution.id  = Connector.sol_id
				WHERE Connector.id = :connId";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue("connId", $connId);
		$stmt->execute();		
		$r = $stmt->fetch();	
		
		// RECUPERE LES PARAMS DE CONNEXION
		$sql = "SELECT id, conn_id, name, value
				FROM ConnectorParam 
				WHERE conn_id = :connId";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue("connId", $connId);
		$stmt->execute();	    
		$tab_params = $stmt->fetchAll();

		$params = array();
		
		if(!empty($tab_params)) {
			foreach ($tab_params as $key => $value) {
				$params[$value['name']] = $value['value'];
				$params['ids'][$value['name']] = array('id' => $value['id'],'conn_id' => $value['conn_id']);
			}			
		}	
		return $params;
	}

}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/solution.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class solution extends solutioncore {
		
	}
}