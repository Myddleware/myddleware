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


class templatecore {

	protected $lang;
	protected $idConnectorSource;
	protected $idConnectorTarget;
	protected $idUser;
	protected $prefixRuleName;
	
    public function __construct(Logger $logger, Container $container, Connection $dbalConnection, $param = false) {
    	$this->logger = $logger;
		$this->container = $container;
		$this->connection = $dbalConnection;	
		if (!empty($param['lang'])) {
			$this->lang = $param['lang'];
		}
		else {
			$this->lang = 'EN';
		}
		if (!empty($param['idConnectorSource'])) {
			$this->idConnectorSource = $param['idConnectorSource'];
		}
		if (!empty($param['idConnectorTarget'])) {
			$this->idConnectorTarget = $param['idConnectorTarget'];
		}
		if (!empty($param['prefixRuleName'])) {
			$this->setPrefixRuleName($param['prefixRuleName']);
		}
		if (!empty($param['idUser'])) {
			$this->idUser = $param['idUser'];
		}
		else{
			$this->idUser = 1;
		}
	}
	
	public function setIdConnectorSource($idConnectorSource) {
		$this->idConnectorSource = $idConnectorSource;
	}
	
	public function setIdConnectorTarget($idConnectorTarget) {
		$this->idConnectorTarget = $idConnectorTarget;
	}
	
	public function setIdUser($idUser) {
		$this->idUser = $idUser;
	}

	public function setLang($lang) {
		$this->lang = $lang;
	}
	
	// On enlève tous les caractères spéciaux du nom prefixRuleName
	public function setPrefixRuleName($prefixRuleName) {
		include_once 'tools.php';
		$this->prefixRuleName = tools::post_slug($prefixRuleName);
	}
	
	// Permet de lister les templates pour les connecteurs selectionnés idConnectorSource et idConnectorTarget
	public function getTemplates() {
		return null;
	}
	
	// Permet de convertir un template en règle lorsque l'utilisateur valide la sélection du template
	public function convertTemplate($idTemplate) {
		// Récupération des requêtes correspondant au template sélectionné
		$queryTemplate = "SELECT tplq_query FROM TemplateQuery WHERE tplt_id = '$idTemplate'";
		$stmt = $this->connection->prepare($queryTemplate);
		$stmt->execute();		
		$queries = $stmt->fetchall();
		$nbRule = 0;
		
		// Lancement de toutes les requêtes du template
		if(!empty($queries)) {
			$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
			try{
				foreach($queries as $query) {
					// Changement d'id rule à chaque nouvelle règle. La requête d'insertion de la règle est toujours la première
					if (substr($query['tplq_query'],0,18) == 'INSERT INTO `Rule`') {
						$idRule = uniqid();
						$nbRule++;
					}
					// Si la règle contient une relation, on récupère l'id de la règle créée pour mettre à jour la relation
					if (substr($query['tplq_query'],0,31) == 'INSERT INTO `RuleRelationShip`') {
						// Récupération du name_slug
						$marqueurDebut = "#BEG#"; 
						$debutLien = strpos( $query['tplq_query'], $marqueurDebut ) + strlen( $marqueurDebut ); 
						$marqueurFin = "#END#"; 
						$finLien = strpos( $query['tplq_query'], $marqueurFin ); 
						$name_slug = substr( $query['tplq_query'], $debutLien, $finLien - $debutLien ); 
						// Ajout du prefix de la règle
						$name_slug_prefix = $this->prefixRuleName.'_'.$name_slug;
						
						// Récupération de l'id de la règle
						$querySlug = "	SELECT Rule.id FROM Rule WHERE name_slug = :name_slug_prefix";
						$stmt = $this->connection->prepare($querySlug);
						$stmt->bindValue("name_slug_prefix", $name_slug_prefix);
						$stmt->execute();	    		
						$rule = $stmt->fetch();

						// remplacement de name_slug par l'id de la règle dans le Myddleware en cours
						$query['tplq_query'] = str_replace($marqueurDebut.$name_slug.$marqueurFin, $rule['id'],$query['tplq_query']);
					}
					// Remplacement des variables pour que les règles soient adaptées à la configuration de Myddleware du client
					$query['tplq_query'] = str_replace('idConnectorSource', $this->idConnectorSource,$query['tplq_query']);
					$query['tplq_query'] = str_replace('idConnectorTarget', $this->idConnectorTarget,$query['tplq_query']);
					$query['tplq_query'] = str_replace('idUser', $this->idUser,$query['tplq_query']);
					$query['tplq_query'] = str_replace('idRule', $idRule,$query['tplq_query']);
					$query['tplq_query'] = str_replace('prefixRuleName', $this->prefixRuleName,$query['tplq_query']);
					$stmt = $this->connection->prepare($query['tplq_query']);
					$stmt->execute();	
				}
				
				// On rafraichit la table order après la création des règles
				include_once 'job.php';
				$job = new job($this->logger, $this->container, $this->connection);
				$job->orderRules();
				
				$this->connection->commit(); // -- COMMIT TRANSACTION
				$session = new Session();
				$session->set( 'info', array("Nous vous recommandons de vous référer à <u><a href=\"http://www.myddleware.fr/index.php/fr/blog/16-tutoriel-fr/13-modeles-predefinis\" target=_blank>l'article suivant</a></u> afin de vous aider à activer ".($nbRule == 1 ? "la règle générée" : "les ".$nbRule." règles générées")." par le modèle prédéfini choisi."));
			} catch (\Exception $e) {
				$session = new Session();
				$session->set( 'error', array("Erreur lors de le génération du template. Contactez le support <A HREF=\"mailto:support@crmconsult.fr\">support@crmconsult.fr</A>",'Failed to generate template : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )'));
				$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
				$this->logger->error( 'Failed to generate template : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
			}		
		}
		else {
			$this->logger->error( 'Failed to create rule. There is no query for this template : '.$idTemplate.'.' );
		}
		return true;
	}
	
	public function generateTemplateHeader($nomTemplate,$descriptionTemplate,$ruleId,$guidTemplate) {
		$sql = '';
		// Récupération des données de la règle
		$query = "	SELECT 
						Rule.*,
						Connector_source.sol_id sol_source,
						Connector_target.sol_id sol_target
					FROM Rule
						INNER JOIN Connector Connector_source
							ON Rule.conn_id_source = Connector_source.id
						INNER JOIN Connector Connector_target
							ON Rule.conn_id_target = Connector_target.id							
					WHERE 
						id = :ruleId";
		$stmt = $this->connection->prepare($query);
		$stmt->bindValue("ruleId", $ruleId);
		$stmt->execute();	    		
		$rule = $stmt->fetch();
		if (empty($rule)) {
			return array('sql' => '', 'error' => 'Failed to load the rule');
		}
			
		// Génération du dump de la table template
		$sql .= "INSERT INTO Template VALUES ('$guidTemplate','$rule[sol_source]','$rule[sol_target]');".chr(10).chr(10);
		$sql .= "INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','$guidTemplate','$nomTemplate','$descriptionTemplate');".chr(10).chr(10);
		return $sql;
	}
	
	public function generateTemplateRule($ruleId,$guidTemplate) {
		$sql = '';
		$query = "	SELECT 
						Rule.*,
						Connector_source.sol_id sol_source,
						Connector_target.sol_id sol_target
					FROM Rule
						INNER JOIN Connector Connector_source
							ON Rule.conn_id_source = Connector_source.id
						INNER JOIN Connector Connector_target
							ON Rule.conn_id_target = Connector_target.id							
					WHERE 
						id = :ruleId";
		$stmt = $this->connection->prepare($query);
		$stmt->bindValue("ruleId", $ruleId);
		$stmt->execute();	    		
		$rule = $stmt->fetch();
		
		// Export table Rule
		$sqlRule = $this->getSqlDump('Rule', $ruleId, $guidTemplate);
		if (empty($sqlRule)) {
			return array('sql' => '', 'error' => 'Failed to generate dump of table Rule');
		}
		$sql .= $sqlRule;
		
		// Export table RuleParam
		$sqlRuleParam = $this->getSqlDump('RuleParam', $ruleId, $guidTemplate);
		if (empty($sqlRuleParam)) {
			return array('sql' => '', 'error' => 'Failed to generate dump of table RuleParam');
		}
		$sql .= $sqlRuleParam;
		
		// Export table RuleFilter	
		$sqlRuleFilter = $this->getSqlDump('RuleFilter', $ruleId, $guidTemplate);
		if (!empty($sqlRuleFilter)) {
			$sql .= $sqlRuleFilter;
		}
		
		// Export table RuleRelationShip	
		$sqlRuleRelationShip = $this->getSqlDump('RuleRelationShip', $ruleId, $guidTemplate);
		if (!empty($sqlRuleRelationShip)) {
			$sql .= $sqlRuleRelationShip;
		}

		// Export table RuleField
		$sqlRuleField = $this->getSqlDump('RuleField', $ruleId, $guidTemplate);
		if (!empty($sqlRuleField)) {
			$sql .= $sqlRuleField;
		}
		
		$prefixTable = 'z_'.$rule['name_slug'].'_'.$rule['version'].'_';
		
		// Récupération de la table source 
		$query = "SHOW CREATE TABLE ".$prefixTable.'source';
		$stmt = $this->connection->prepare($query);
		$stmt->execute();	    		
		$tableSource = $stmt->fetch();
		if (empty($tableSource['Create Table'])) {
			return array('sql' => '', 'error' => 'Failed to load the table source');
		}
		// Remplacement du nom de la règle
		$sqlSource = str_replace($rule['name_slug'],'prefixRuleName_'.$rule['name_slug'],$tableSource['Create Table']).';';
		$sql .= "INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('$guidTemplate', '$sqlSource');".chr(10).chr(10);
		
		// Récupération de la table target 
		$query = "SHOW CREATE TABLE ".$prefixTable.'target';
		$stmt = $this->connection->prepare($query);
		$stmt->execute();	    		
		$tableTarget = $stmt->fetch();
		if (empty($tableTarget['Create Table'])) {
			return array('sql' => '', 'error' => 'Failed to load the table target');
		}
		// Remplacement du nom de la règle
		$sqlTarget = str_replace($rule['name_slug'],'prefixRuleName_'.$rule['name_slug'],$tableTarget['Create Table']).';';
		$sql .= "INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('$guidTemplate', '$sqlTarget');".chr(10).chr(10);
		
		// Récupération de la table history 
		$query = "SHOW CREATE TABLE ".$prefixTable.'history';
		$stmt = $this->connection->prepare($query);
		$stmt->execute();	    		
		$tableHistory = $stmt->fetch();
		if (empty($tableHistory['Create Table'])) {
			return array('sql' => '', 'error' => 'Failed to load the table history');
		}
		$sqlHistory = str_replace($rule['name_slug'],'prefixRuleName_'.$rule['name_slug'],$tableHistory['Create Table']).';';
		$sql .= "INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('$guidTemplate', '$sqlHistory');".chr(10).chr(10);
		
		return array('sql' => $sql, 'error' => '');
	}
	
	// Extraction des données d'un règle
	protected function getSqlDump($table, $ruleId, $guidTemplate) {
		$sql = '';
		$values = '';
		$break = false;
		$nextDateReference = false;
		$query = "SELECT * FROM $table WHERE id = :ruleId";
		$stmt = $this->connection->prepare($query);
		$stmt->bindValue("ruleId", $ruleId);
		$stmt->execute();	    		
		$rows = $stmt->fetchall();
		if (!empty($rows)) {
			$firstRow = true;
			$fields = '';
			foreach ($rows as $row) {
				// Prise en compte du cas où on n'a qu'une seule ligne dans le résultat de requête, on remet alors la ligne complète dans $row poru que la boucle suivant puisse écrire la requpete
				if (is_string($row)) {
					$row = $rows;
					$break = true;
				}
				$firstfield = true;
				$values .= '(';
				//Ajout de tous les champs dans le dump
				foreach ($row as $key => $value) {
					// Si on est sur une table autre que Rule, on ne garde pas le premier paramètre qui est un id qui doit s'incrémenter
					if ($firstfield && $table != 'Rule') {
						$firstfield = false;
						continue;
					}
					// Si on est sur la première ligne, on sauvegarde le header
					if ($firstRow) {
						$fields .= "`$key`,";
					}
					// Pour certaine données on insère la clé et non la valeur car ces données seront des paramètres
					if ($key == 'id') {
						$values .= "'idRule',";
					}
					elseif ($key == 'conn_id_source') {
						$values .= "'idConnectorSource',";
					}
					elseif ($key == 'conn_id_target') {
						$values .= "'idConnectorTarget',";
					}
					// Par défaut une règle est inactive
					elseif ($key == 'active') {
						$values .= "'0',";
					}
					elseif ($key == 'name') {
						$values .= "'prefixRuleName_".$row['name_slug']."',";
					}
					elseif ($key == 'name_slug') {
						$values .= "'prefixRuleName_".$row['name_slug']."',";
					} 
					elseif (in_array($key, array('created_by','modified_by'))) {
						$values .= "'idUser',";
					}
					// Les dates doivent être dynamiques
					elseif(in_array($key, array('date_created','date_modified'))) {
						$values .= "NOW(),";
					}
					// La date de référence est égale à aujourd'hui à minuit
					elseif ($nextDateReference) {
						$values .= "CONCAT( CURDATE( ),' 00:00:00' ),";
						$nextDateReference = false;
					}
					// Il faut gérer les " dans les formule
					elseif($key == 'formula') {
						$values .= "'".addslashes($value)."',";
					}
					// S'il s'agit d'une relation il faut mettre un code pour pouvoir mettre l'id de la règle lié
					elseif($key == 'field_id') {
						// Récupération du name_slug de la règle liée
						$query = "	SELECT Rule.name_slug FROM Rule WHERE id = :ruleId";
						$stmt = $this->connection->prepare($query);
						$stmt->bindValue("ruleId", $value);
						$stmt->execute();	    		
						$rule = $stmt->fetch();
						$values .= "'#BEG#$rule[name_slug]#END#',";
					}
					else {
						$values .= "'".$value."',";
					}
					// Gestion de la date de référence
					if ($value == 'datereference' && $table == 'RuleParam') {
						$nextDateReference = true;
					}
				}
				$firstRow = false;
				// Suppression de la dernière virgule er gestion de la fin de ligne
				$values = rtrim($values,','); 
				$values .= '),'.chr(10);
				if ($break) {
					break;
				}
			}
			// Suppression de la dernière virgule er gestion de la fin de requête
			$values = rtrim($values,chr(10)); 
			$values = rtrim($values,','); 
			$fields = rtrim($fields,','); 
			$sql = "INSERT INTO `$table` ($fields) VALUES $values ";
			
			// Création de la requête finale
			$sql = "INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('$guidTemplate', \"$sql\");".chr(10).chr(10);
		}
		return $sql;
	}
	
	public function refreshTemplate() {
		$this->connection->beginTransaction(); // -- BEGIN TRANSACTION
		try {
			// Suppression des données dans les tables template
			$clearTable = $this->clearTable();
			if ($clearTable['done'] === false) {
				throw new \Exception( $clearTable['error'] );
			}
			
			// Permet de charger tous les templates
			$loadTemplates = $this->loadTemplates();
			if ($loadTemplates['done'] === false) {
				throw new \Exception( $loadTemplates['error'] );
			}
		
			$this->connection->commit(); // -- COMMIT TRANSACTION
			return array('done' => true, 'error' => '');
		} catch (\Exception $e) {
			$this->connection->rollBack(); // -- ROLLBACK TRANSACTION
			return array('done' => false, 'error' => $e->getMessage());
		}
	}
	
	// Suppression des données dans les tables template
	protected function clearTable() {
		try {
			$query = "DELETE FROM Template";
			$stmt = $this->connection->prepare($query);
			$stmt->execute();	

			$query = "DELETE FROM TemplateLang";
			$stmt = $this->connection->prepare($query);
			$stmt->execute();

			$query = "DELETE FROM TemplateQuery";
			$stmt = $this->connection->prepare($query);
			$stmt->execute();
			return array('done' => true, 'error' => '');
		} catch (\Exception $e) {
			return array('done' => false, 'error' => $e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )');
		}
	}
	
	// Permet de charger tous les templates
	protected function loadTemplates() {
		try {
			$dir = __DIR__.'/../Templates/';
			$d = dir($file = $dir); 
			while ($entry = $d->read()) { 
				if(!empty($entry)){ 
					$queryFile = file_get_contents($dir.$entry);
					$queries = explode(";\n", $queryFile);
					if (!empty($queries)) {
						foreach ($queries as $query) {
							if (!empty($query) && $query != chr(10)) {
								$stmt = $this->connection->prepare($query);
								$stmt->execute();
							}
						}
					}
				} 
			} 
			// Permet de rattraper une erreur généré par 2 guillements dans les create table
			$query = "UPDATE TemplateQuery SET tplq_query = REPLACE(tplq_query, 'NOT NULL DEFAULT \'', 'NOT NULL DEFAULT \'\'')";
			$stmt = $this->connection->prepare($query);
			$stmt->execute();
			$d->close();
			return array('done' => true, 'error' => '');
		} catch (\Exception $e) {
			return array('done' => false, 'error' => $e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )');
		}
	}
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/template.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class template extends templatecore {
		
	}
}
?>