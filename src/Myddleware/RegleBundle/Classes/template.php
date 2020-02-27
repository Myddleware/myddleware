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
use Myddleware\RegleBundle\Entity\Rule;
use Myddleware\RegleBundle\Entity\RuleParam;
use Myddleware\RegleBundle\Entity\RuleFilter;
use Myddleware\RegleBundle\Entity\RuleField;
use Myddleware\RegleBundle\Entity\RuleRelationShip;

class templatecore {

	protected $lang;
	protected $solutionSourceName;
	protected $solutionTarget;
	protected $connectorSource;
	protected $connectorTarget;
	protected $idUser;
	protected $prefixRuleName;
	protected $templateDir;
	protected $rules;
	protected $ruleNameSlugArray;
	protected $em;
	protected $sourceSolution;
	protected $targetSolution;
	
    public function __construct(Logger $logger, Container $container, Connection $dbalConnection, $param = false) {
    	$this->logger = $logger;
		$this->container = $container;
		$this->connection = $dbalConnection;	
		$this->em = $this->container->get('doctrine')->getEntityManager();
		$this->templateDir = $this->container->getParameter('kernel.root_dir').'/../src/Myddleware/RegleBundle/Templates/';
		if (!empty($param['lang'])) {
			$this->lang = $param['lang'];
		}
		else {
			$this->lang = 'EN';
		}
		if (!empty($param['solutionSourceName'])) {
			$this->solutionSourceName = $param['solutionSourceName'];
		}
		if (!empty($param['solutionTarget'])) {
			$this->solutionTarget = $param['solutionTarget'];
		}
		if (!empty($param['idUser'])) {
			$this->idUser = $param['idUser'];
		}
		else{
			$this->idUser = 1;
		}
	}
	
	public function setIdConnectorSource($connectorSource) {
		$this->connectorSource = $this->container->get('doctrine')
								  ->getEntityManager()
								  ->getRepository('RegleBundle:Connector')
								  ->findOneById($connectorSource);
		// When the the connector is set, we set the solution too
		$this->solutionSourceName = $this->connectorSource->getSolution()->getName();
	}
	
	public function setIdConnectorTarget($connectorTarget) {
		$this->connectorTarget = $this->container->get('doctrine')
								  ->getEntityManager()
								  ->getRepository('RegleBundle:Connector')
								  ->findOneById($connectorTarget);
		// When the the connector is set, we set the solution too
		$this->solutionTarget = $this->connectorTarget->getSolution()->getName();
	}
	
	public function setsolutionSourceName($solutionSourceName) {
		$this->solutionSourceName = $solutionSourceName;
	}
	
	public function setSolutionTarget($solutionTarget) {
		$this->solutionTarget = $solutionTarget;
	}
	
	public function setIdUser($idUser) {
		$this->idUser = $idUser;
	}

	public function setLang($lang) {
		$this->lang = $lang;
	}
	
	// Sort rule (rule parent first)
	public function setRules($rules) {
		$this->rules = $rules;
		$rulesString = trim(implode(',',$rules));
		$query = "SELECT rule_id FROM RuleOrder WHERE FIND_IN_SET(`rule_id`,:rules) ORDER BY RuleOrder.order ASC";
		$stmt = $this->connection->prepare($query);
		$stmt->bindValue("rules", $rulesString);
		$stmt->execute();	    		
		return $stmt->fetchALL();
	}

	
	// Permet de lister les templates pour les connecteurs selectionnés 
	public function getTemplates() {
		$templates = array();
		// Read in the directory template, we read files  corresponding to the selected solutions
		foreach (glob($this->templateDir.$this->solutionSourceName.'_'.$this->solutionTarget.'*.yml') as $filename) {
			$template = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($filename));
			$templates[] = $template;
		}
		return $templates;
	}
	
	// Extract all the data of the rule
	public function extractRule($ruleId) {
		// General data
		$rule = $this->em->getRepository('RegleBundle:Rule')->findOneBy(array('id' => $ruleId));
		if (empty($rule)) {
			throw new \Exception('Failed to get general data from the rule '.$ruleId);
		}
		$data['name'] = $rule->getName();
		$data['nameSlug'] = $rule->getNameSlug();
		$data['sourceSolution'] = $rule->getConnectorSource()->getSolution()->getName();
		$data['targetSolution'] = $rule->getConnectorTarget()->getSolution()->getName();
		$data['sourceModule'] = $rule->getModuleSource();
		$data['targetModule'] = $rule->getModuleTarget();
		
		// Save all rule name slug to be able to create relationship without keeping the rule id
		$this->ruleNameSlugArray[$ruleId] = $data['nameSlug'];

		// Rule fields
		$ruleFields = $this->em->getRepository('RegleBundle:RuleField')->findByRule($ruleId);
		if($ruleFields) {
			foreach ($ruleFields as $ruleField) {
				$field['target'] = $ruleField->getTarget();
				$field['source'] = $ruleField->getSource();
				$field['formula'] = $ruleField->getFormula();
				$data['fields'][] = $field;
			}
		}
		
		// Rule RelationShips
		$ruleRelationShips = $this->em->getRepository('RegleBundle:RuleRelationShip')->findByRule($ruleId);
		if($ruleRelationShips) {				
			foreach ($ruleRelationShips as $ruleRelationShip) {
				$relationship['fieldNameSource'] = $ruleRelationShip->getFieldNameSource();
				$relationship['fieldNameTarget'] = $ruleRelationShip->getFieldNameTarget();
				if (!empty($this->ruleNameSlugArray[$ruleRelationShip->getFieldId()])) {
					$relationship['fieldId'] = $this->ruleNameSlugArray[$ruleRelationShip->getFieldId()];
				} else {
					throw new \Exception('Failed to generate relationship for the rule '.$ruleId);
				}
				$relationship['parent'] = $ruleRelationShip->getParent();
				$data['relationships'][] = $relationship;
			}
		}
		
		// Rule Filters
		$ruleFilters = $this->em->getRepository('RegleBundle:RuleFilter')->findByRule($ruleId);
		if($ruleFilters) {				
			foreach ($ruleFilters as $ruleFilter) {
				$filter['target'] = $ruleFilter->getTarget();
				$filter['type'] = $ruleFilter->getType();
				$filter['value'] = $ruleFilter->getValue();
				$data['filters'][] = $filter;
			}
		}

		// Rule Params
		$ruleParams = $this->em->getRepository('RegleBundle:RuleParam')->findByRule($ruleId);
		if($ruleParams) {				
			foreach ($ruleParams as $ruleParam) {
				$param['name'] = $ruleParam->getName();
				// If reference date we set it far in the past or to 0 if it is a numeric
				if ($param['name'] == 'datereference') {
					if (is_numeric($ruleParam->getValue())) {
						$param['value'] = 0;
					} else { // date
						$param['value'] = '1970-01-01 00:00:00';
					}
				} else {
					$param['value'] = $ruleParam->getValue();
				}
				$data['params'][] = $param;
			}
		}
		return $data;
	}
	
	protected function initSolutions() {
		// Init source solution
		$this->sourceSolution = $this->container->get('myddleware_rule.'.$this->solutionSourceName);	
		$connectorParams = $this->em->getRepository('RegleBundle:ConnectorParam')->findByConnector($this->connectorSource);	
		foreach ($connectorParams as $connectorParam) {
			$params[$connectorParam->getName()] = $connectorParam->getValue();
		}	
		// Throw exception in the login in case the connection doesn't work
		$this->sourceSolution->login($params);
	
		// Init source solution	
		$this->targetSolution = $this->container->get('myddleware_rule.'.$this->solutionTarget);	
		$connectorParams = $this->em->getRepository('RegleBundle:ConnectorParam')->findByConnector($this->connectorTarget);	
		foreach ($connectorParams as $connectorParam) {
			$params[$connectorParam->getName()] = $connectorParam->getValue();
		}	
		// Throw exception in the login in case the connection doesn't work
		$this->targetSolution->login($params);
	}

	// Permet de convertir un template en règle lorsque l'utilisateur valide la sélection du template
	public function convertTemplate($ruleName,$templateName) {
		$this->em->getConnection()->beginTransaction(); // -- BEGIN TRANSACTION
		try{
			// Get the template array
			$template = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->templateDir.$templateName.'.yml'));
			if (empty($template['rules'])) {
				throw new \Exception('No rule found in the template. ');
			}
			
			// Login to the source and target solution
			$sourceModule = $this->initSolutions();
			
			// Get list of module for each solution for source and target
			$sourceModuleListSource = $this->sourceSolution->get_modules('source');
			$sourceModuleListTarget = $this->sourceSolution->get_modules('target');
			$targetModuleListSource = $this->targetSolution->get_modules('source');
			$targetModuleListTarget = $this->targetSolution->get_modules('target');
			$nbRule = 0;
			foreach($template['rules'] as $rule) {
				// Rule creation
				// General data
				$ruleObject = new Rule();
				$ruleObject->setName((string)$ruleName.' - '.$rule['name']);
				$ruleObject->setNameSlug((string)$ruleName.'_'.$rule['nameSlug']);
				// It is possible that the templatte contains opposite rules, so we test it first. If solution are opposite, we set opposite connectors too
				if ($rule['sourceSolution'] == $this->solutionSourceName) {
					$ruleObject->setConnectorSource($this->connectorSource);
					// Check the access to the module
					if (empty($sourceModuleListSource[$rule['sourceModule']])) {
						throw new \Exception('Module '.$rule['sourceModule'].' not found in '.$rule['sourceSolution'].'. Please make sure that you have access to this module. ');
					}
				} elseif ($rule['sourceSolution'] == $this->solutionTarget) {
					$ruleObject->setConnectorSource($this->connectorTarget);
					// Check the access to the module, in case we are in an opposite rule, we search in the target module list for source module
					if (empty($targetModuleListSource[$rule['sourceModule']])) {
						throw new \Exception('Module '.$rule['sourceModule'].' not found in '.$rule['sourceSolution'].'. Please make sure that you have access to this module. ');
					}										
				}else {
					throw new \Exception('No correspondance between source solutions. ');
				}
				
				// The same for target connector 
				if ($rule['targetSolution'] == $this->solutionTarget) {
					$ruleObject->setConnectorTarget($this->connectorTarget);
					// Check the access to the module
					if (empty($targetModuleListTarget[$rule['targetModule']])) {
						throw new \Exception('Module '.$rule['targetModule'].' not found in '.$rule['targetSolution'].'. Please make sure that you have access to this module. ');
					}
				} elseif ($rule['targetSolution'] == $this->solutionSourceName) {
					$ruleObject->setConnectorTarget($this->connectorSource);	
					// Check the access to the module, in case we are in an opposite rule, we search in the source module list for target module
					if (empty($sourceModuleListTarget[$rule['targetModule']])) {
						throw new \Exception('Module '.$rule['targetModule'].' not found in '.$rule['targetSolution'].'. Please make sure that you have access to this module. ');
					}
				} else {
					throw new \Exception('No correspondance between source solutions. ');
				}
				
				$ruleObject->setDateCreated(new \DateTime);
				$ruleObject->setDateModified(new \DateTime);
				$ruleObject->setCreatedBy((int)$this->idUser);
				$ruleObject->setModifiedBy((int)$this->idUser);	
				$ruleObject->setModuleSource((string)$rule['sourceModule']);
				$ruleObject->setModuleTarget((string)$rule['targetModule']);	
				$ruleObject->setDeleted(0);
				if (!empty($rule['active'])) {
					$ruleObject->setActive(1);
				} else {
					$ruleObject->setActive(0);
				}
				$this->em->persist($ruleObject);
		
				// We save the rule to be able to create relationship in the orther rules
				$this->ruleNameSlugArray[$rule['nameSlug']] = $ruleObject->getId();
				
			 	$sourceFieldsList = '';
				$targetFieldsList = '';
				// Get the field list for source and target solution
				if (
						$rule['sourceSolution'] == $this->solutionSourceName
					 && $rule['targetSolution'] == $this->solutionTarget	
				) {
					$sourceFieldsList = $this->sourceSolution->get_module_fields($rule['sourceModule']);
					$targetFieldsList = $this->targetSolution->get_module_fields($rule['targetModule']);
				}
				elseif (
						$rule['sourceSolution'] == $this->solutionSourceName
					 && $rule['targetSolution'] == $this->solutionTarget	
				) {
					$sourceFieldsList = $this->targetSolution->get_module_fields($rule['sourceModule']);
					$targetFieldsList = $this->sourceSolution->get_module_fields($rule['targetModule']);
				} else {
					throw new \Exception('No correspondance between source solutions. Failed to get the field list. ');
				}
				
				// Create rule fields
				if (!empty($rule['fields'])) {
					foreach ($rule['fields'] as $field) {
						// Check that all fields are available
						// Check source, several fields can be store in source in cas of formula
						$sourceFields = explode(';',$field['source']);
						foreach($sourceFields as $sourceField) {
							if (
									empty($sourceFieldsList[$sourceField])
								 && $sourceField != 'my_value'	
							) {
								throw new \Exception('Field '.$sourceField.' not found in the module '.$rule['sourceModule'].'. Please make sure that you have access to this field. ');
							}
						}
						// Check target field
						if (
								empty($targetFieldsList[$field['target']])
							 && $field['target'] != 'my_value'	
						) {
							throw new \Exception('Field '.$field['target'].' not found in the module '.$rule['targetModule'].'. Please make sure that you have access to this field. ');
						}
						$fieldObject = new RuleField();
						$fieldObject->setRule($ruleObject->getId());
						$fieldObject->setTarget($field['target']);	
						$fieldObject->setSource($field['source']);	
						$fieldObject->setFormula($field['formula']);	
						$this->em->persist($fieldObject);
					}
				}
				
				// Create rule relationship
				if (!empty($rule['relationships'])) {
					foreach ($rule['relationships'] as $relationship) {								
						// Check source field				
						if (
								empty($sourceFieldsList[$relationship['fieldNameSource']])
							 && $relationship['fieldNameSource'] != 'Myddleware_element_id'	
						) {									
							throw new \Exception('Field '.$relationship['fieldNameSource'].' not found in the module '.$rule['sourceModule'].'. Please make sure that you have access to this field. ');
						}
						// Check target field
						if (
								(
										empty($relationship['parent'])
									AND	empty($targetFieldsList[$relationship['fieldNameTarget']])
									AND	$relationship['fieldNameTarget'] != 'Myddleware_element_id'		
								)
								// We check source field in case of child rule
								OR	
								(
										!empty($relationship['parent'])
									AND empty($sourceFieldsList[$relationship['fieldNameTarget']]) 	
									AND	$relationship['fieldNameSource'] != 'Myddleware_element_id'
								)
						) {						
							throw new \Exception('Field '.$relationship['fieldNameTarget'].' not found in the module '.(empty($relationship['parent']) ? $rule['targetModule'] : $rule['sourceModule']).'. Please make sure that you have access to this field. ');
						}
						$relationshipObjecy = new RuleRelationShip();
						$relationshipObjecy->setRule($ruleObject->getId());
						$relationshipObjecy->setFieldNameSource($relationship['fieldNameSource']);
						$relationshipObjecy->setFieldNameTarget($relationship['fieldNameTarget']);
						// fieldId contains the nameSlug, we have to change it with the id of the relate rule 					
						$relationshipObjecy->setFieldId($this->ruleNameSlugArray[$relationship['fieldId']]); 
						$relationshipObjecy->setParent($relationship['parent']);
						$relationshipObjecy->setDeleted(0);
						$this->em->persist($relationshipObjecy);
					}
				}
				
				// Create rule filter
				if (!empty($rule['filters'])) {
					foreach ($rule['filters'] as $filter) {
						$ruleFilterObject = new RuleFilter();	
						$ruleFilterObject->setRule($ruleObject->getId());
						$ruleFilterObject->setTarget($filter['target']);
						$ruleFilterObject->setType($filter['type']);
						$ruleFilterObject->setValue($filter['value']);
						$this->em->persist($ruleFilterObject);
					}
				}
				
				// Create rule param
				if (!empty($rule['params'])) {
					foreach ($rule['params'] as $param) {
						$ruleParamObject = new RuleParam();	
						$ruleParamObject->setRule($ruleObject->getId());
						$ruleParamObject->setName($param['name']);
						$ruleParamObject->setValue($param['value']);
						$this->em->persist($ruleParamObject);
					}
				} 
				$nbRule++;
			}
			
			// Commit the rules in the database
			$this->em->flush();
			$this->em->getConnection()->commit(); // -- COMMIT TRANSACTION
			// Refresh table order
			include_once 'job.php';
			$job = new job($this->logger, $this->container, $this->connection);
			$job->orderRules();		
			// Set the message in Myddleware UI
			$session = new Session();
			$session->set( 'info', array($this->container->get('translator')->trans('messages.template.nb_rule').$nbRule, $this->container->get('translator')->trans('messages.template.help')));
		} catch (\Exception $e) {
			// Rollback in case of error
			$this->em->getConnection()->rollBack(); // -- ROLLBACK TRANSACTION
			$session = new Session();
			$session->set( 'error', array($this->container->get('translator')->trans('error.template.creation'),$e->getMessage()));
			$error = 'Failed to generate rules : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )'; 
			$this->logger->error($error);
			return $error;
		}		
		return true;
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