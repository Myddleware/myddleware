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

namespace App\Manager;

use Exception;
use App\Entity\Job;
use App\Entity\Rule;
use App\Entity\Document;
use App\Entity\RuleOrder;
use App\Entity\RuleParam;
use Psr\Log\LoggerInterface;
use App\Repository\RuleRepository;
use Doctrine\DBAL\Driver\Connection;
use App\Repository\DocumentRepository;
use App\Repository\RuleOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\RuleRelationShipRepository;
use Symfony\Component\Routing\RouterInterface;
use App\Entity\RuleParamAudit as RuleParamAudit;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType; // Tools
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

$file = __DIR__.'/../Custom/Manager/RuleManager.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class RuleManager
    {
        protected $connection;
        protected $logger;
        protected $ruleId;
        protected $rule;
        protected $ruleFields;
        protected $ruleParams;
        protected $sourceFields;
        protected $targetFields;
        protected $ruleRelationships;
        protected $ruleFilters;
        protected $solutionSource;
        protected $solutionTarget;
        protected $jobId;
        protected $manual;
        protected $key;
        protected $limit = 100;
        protected $limitReadCommit = 1000;
        protected $tools;
        protected $api;    // Specify if the class is called by the API
        /**
         * @var EntityManagerInterface
         */
        private $entityManager;
        /**
         * @var ParameterBagInterface
         */
        private $params;
        private $cacheDir;
        /**
         * @var document
         */
        private $document;
        /**
         * @var string
         */
        private $env;
        /**
         * @var RouterInterface
         */
        private $router;
        /**
         * @var RuleRepository
         */
        private $ruleRepository;
        /**
         * @var RuleRelationShipRepository
         */
        private $ruleRelationShipRepository;
        /**
         * @var SolutionManager
         */
        private $solutionManager;
        /**
         * @var DocumentRepository
         */
        private $documentRepository;
        /**
         * @var RuleOrderRepository
         */
        private $ruleOrderRepository;
        /**
         * @var SessionInterface
         */
        private $session;

        public function __construct(
            LoggerInterface $logger,
            Connection $connection,
            EntityManagerInterface $entityManager,
            RuleRepository $ruleRepository,
            RuleRelationShipRepository $ruleRelationShipRepository,
            RuleOrderRepository $ruleOrderRepository,
            DocumentRepository $documentRepository,
            RouterInterface $router,
            KernelInterface $kernel,
            SessionInterface $session,
            SolutionManager $solutionManager,
            ToolsManager $tools,
            DocumentManager $document
        ) {
            $this->logger = $logger;
            $this->connection = $connection;
            $this->entityManager = $entityManager;
            $this->ruleRepository = $ruleRepository;
            $this->ruleRelationShipRepository = $ruleRelationShipRepository;
            $this->ruleOrderRepository = $ruleOrderRepository;
            $this->documentRepository = $documentRepository;
            $this->tools = $tools;
            $this->router = $router;
            $this->session = $session;
            $this->solutionManager = $solutionManager;
            $this->document = $document;
            $this->cacheDir = $kernel->getCacheDir();
            $this->env = $kernel->getEnvironment();
        }

        /**
         *  Generate a document for the current rule for a specific id in the source application. We don't use the reference for the function read.
         *  If parameter readSource is false, it means that the data source are already in the parameter param, so no need to read in the source application.
         *
         * @return Document[]
         *
         * @param mixed $idSource
         * @param mixed $readSource
         * @param mixed $param
         * @param mixed $idFiledName
         */
        public function generateDocuments(Rule $rule, $idSource, $readSource = true, $param = [], $idFiledName = 'id')
        {
            try {
                if ($readSource) {
                    // Connection to source application
                    $connexionSolution = $this->connexionSolution($rule, 'source');
                    if (false === $connexionSolution) {
                        throw new Exception('Failed to connect to the source solution.');
                    }

                    // Read data in the source application
                    // If the query is in the current record we replace Myddleware_element_id by id
                    if ('Myddleware_element_id' == $idFiledName) {
                        $idFiledName = 'id';
                    }
                    $read = [
                        'module' => $rule->getModuleSource(),
                        'fields' => $rule->getSourceFields(),
                        'ruleParams' => $rule->getParamsValues(),
                        'rule' => $rule,
                        'query' => [$idFiledName, $idSource],
                        // In case we search a specific record, we set an default value in date_ref because it is a requiered parameter in the read function
                        'date_ref' => '1970-01-02 00:00:00',
                    ];
                    $dataSource = $this->solutionSource->read($read);
                    if (!empty($dataSource['error'])) {
                        throw new Exception('Failed to read record '.$idSource.' in the module '.$read['module'].' of the source solution. '.(!empty($dataSource['error']) ? $dataSource['error'] : ''));
                    }
                } else {
                    $dataSource['values'][] = $param['values'];
                }

                $documents = [];
                if (!empty($dataSource['values'])) {
                    foreach ($dataSource['values'] as $docData) {
                        // Generate document
                        // If the document is a child, we save the parent in the table Document
                        if (!empty($param['parent_id']) && empty($docData['parent_id'])) {
                            $docData['parentId'] = $param['parent_id'];
                        }
                        $response = $this->document->createDocument($rule, $docData);
                        if (!$response['success']) {
                            throw new Exception('Failed to create document : '.$response['message']);
                        }
                        $documents[] = $response['document'];
                    }
                }

                return $documents;
            } catch (Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($error);

                return [];
            }
        }

        // Connect to the source or target application
        public function connexionSolution(Rule $rule, $type)
        {
            if (!in_array($type, ['source', 'target'])) {
                return false;
            }
            $connector = 'source' == $type ? $rule->getConnectorSource() : $rule->getConnectorTarget();
            $params = [];
            foreach ($connector->getConnectorParams() as $connectorParam) {
                $params[$connectorParam->getName()] = $connectorParam->getValue();
                $params['ids'][$connectorParam->getName()] = ['id' => $connectorParam->getId(), 'conn_id' => $connector->getId()];
            }

            try {
                // Connect to the application
                if ('source' == $type) {
                    $this->solutionSource = $this->solutionManager->get($connector->getSolution()->getName());
                    $this->solutionSource->setApi($this->api);
                    $loginResult = $this->solutionSource->login($params);
                    $c = $this->solutionSource->connexion_valide ? true : false;
                } else {
                    $this->solutionTarget = $this->solutionManager->get($connector->getSolution()->getName());
                    $this->solutionTarget->setApi($this->api);
                    $loginResult = $this->solutionTarget->login($params);
                    $c = $this->solutionTarget->connexion_valide ? true : false;
                }
                if (!empty($loginResult['error'])) {
                    return $loginResult;
                }

                return $c;
            } catch (Exception $e) {
                $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

                return false;
            }
        }

        // Permet de mettre toutes les données lues dans le système source dans le tableau $this->dataSource
        // Cette fonction retourne le nombre d'enregistrements lus
        public function createDocuments(Rule $rule, Job $job, bool $manual = false): array
        {
            $disableReadRuleParam = $rule->getParamByName('disableRead');
            if ($disableReadRuleParam) {
                return ['success' => false];
            }

            $response = [];
            if (
                (!$rule->getDeleted() && $rule->getActive())
                || true === $manual
            ) {
                $dataSource = [];
                // lecture des données dans la source
                $read = $this->readSource($rule, $job);
                if (!empty($read['fields'])) {
                    $connect = $this->connexionSolution($rule, 'source');
                    if (true === $connect) {
                        $solutionSource = $this->solutionManager->get($rule->getConnectorSource()->getSolution()->getName());
                        $dataSource = $solutionSource->read($read);

                        // If Myddleware has reached the limit, we validate data to make sure no doto won't be lost
                        if (!empty($dataSource['count']) && $dataSource['count'] == $this->limit) {
                            // Check and clean data source
                            $validateReadDataSource = $this->validateReadDataSource($dataSource);
                            if (!empty($validateReadDataSource['error'])) {
                                // If the run isn't validated, we set back the previous reference date
                                // so Myddleware won't continue to read next data during the next run
                                $dataSource['date_ref'] = $read['date_ref'];
                                $readSource = $validateReadDataSource;
                            }
                        }
                        // Logout (source solution)
                        if ($solutionSource) {
                            $loginResult = $solutionSource->logout();
                            if (!$loginResult) {
                                $dataSource['error'] .= 'Failed to logout from the source solution';
                            }
                        }

                        $readSource = $dataSource;
                    } elseif (!empty($connect['error'])) {
                        $readSource = $connect;
                    } else {
                        $readSource = ['error' => 'Failed to connect to the source with rule : '.$rule->getId().' .'];
                    }
                }

                if (empty($readSource['error'])) {
                    $readSource['error'] = '';
                }

                // Si erreur
                if (!isset($readSource['count'])) {
                    return $readSource;
                }

                try {
                    if ($readSource['count'] > 0) {
                        $param['rule'] = $rule;
                        $param['ruleFields'] = $rule->getFields();
                        $param['ruleRelationships'] = $rule->getRelationsShip();

                        if ($dataSource['values']) {
                            // If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->checkRecordExist
                            $migrationParameters = $this->params->get('migration');
                            if (!empty($migrationParameters['mode'])) {
                                $param['ruleDocuments'][$rule->getId()] = $this->getRuleDocuments($rule);
                            }
                            // Boucle sur chaque document
                            $i = 0;
                            foreach ($dataSource['values'] as $row) {
                                if ($i >= $this->limitReadCommit) {
                                    $this->entityManager->flush();
                                    $i = 0;
                                }
                                ++$i;
                                $response = $this->document->createDocument($rule, $row);
                                if (false === $response['success']) {
                                    $readSource['error'] .= $response['message'];
                                }
                            }
                            $this->entityManager->flush();
                        }
                        // Mise à jour de la date de référence si des documents ont été créés
                        $this->updateReferenceDate();
                    }
                    // If params has been added in the output of the rule we saved it
                    $this->updateParams();

                    // Rollback if the job has been manually stopped
                    if ('Start' != $job->getStatus()) {
                        throw new Exception('The task has been stopped manually during the document creation. No document generated. ');
                    }
                    $this->connection->commit(); // -- COMMIT TRANSACTION
                } catch (Exception $e) {
                    $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                    $this->logger->error('Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                    $readSource['error'] = 'Failed to create documents : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                }
            } else {
                $response['error'] = 'The rule '.$this->rule['name_slug'].(1 == $this->rule['deleted'] ? ' is deleted.' : ' is disabled.');
            }

            return $response;
        }

        // Permet de mettre à jour la date de référence pour ne pas récupérer une nouvelle fois les données qui viennent d'être écrites dans la cible
        protected function updateReferenceDate()
        {
            $param = $this->entityManager->getRepository(RuleParam::class)
                ->findOneBy([
                    'rule' => $this->ruleId,
                    'name' => 'datereference',
                ]);
            // Every rules should have the param datereference
            if (empty($param)) {
                throw new Exception('No reference date for the rule '.$this->ruleId.'.');
            }
            // Save param modification in the audit table
            if ($param->getValue() != $this->dataSource['date_ref']) {
                $paramAudit = new RuleParamAudit();
                $paramAudit->setRuleParamId($param->getId());
                $paramAudit->setDateModified(new \DateTime());
                $paramAudit->setBefore($param->getValue());
                $paramAudit->setAfter($this->dataSource['date_ref']);
                $paramAudit->setJob($this->jobId);
                $this->entityManager->persist($paramAudit);
            }
            // Update reference
            $param->setValue($this->dataSource['date_ref']);
            $this->entityManager->persist($param);
            $this->entityManager->flush();
        }

        // Update/create rule parameter
        protected function updateParams()
        {
            if (!empty($this->dataSource['ruleParams'])) {
                foreach ($this->dataSource['ruleParams'] as $ruleParam) {
                    // Search to check if the param already exists
                    $paramEntity = $this->entityManager->getRepository(RuleParam::class)
                        ->findOneBy([
                            'rule' => $this->ruleId,
                            'name' => $ruleParam['name'],
                        ]
                        );
                    // Update or create the new param
                    if (!empty($paramEntity)) {
                        $paramEntity->setValue($ruleParam['value']);
                    } else {
                        $paramEntity = new RuleParam();
                        $paramEntity->setRule($this->ruleId);
                        $paramEntity->setName($ruleParam['name']);
                        $paramEntity->setValue($ruleParam['value']);
                    }
                    $this->entityManager->persist($paramEntity);
                    $this->entityManager->flush();
                }
            }
        }

        /**
         * @return array
         */
        private function readSource(Rule $rule, Job $job)
        {
            $dateReference = $rule->getParamByName('datereference');
            $read['module'] = $rule->getModuleSource();
            $read['rule'] = $rule;
            $read['date_ref'] = $dateReference ? $dateReference->getValue() : null;
            $read['ruleParams'] = $rule->getParamsValues();
            $read['fields'] = $rule->getSourceFields();
            $read['offset'] = 0;
            $read['limit'] = $this->limit;
            $read['jobId'] = $job->getId();
            $read['manual'] = $this->manual;
            // Ajout des champs source des relations de la règle
            if ($rule->getRelationsShip()->count()) {
                foreach ($rule->getRelationsShip() as $ruleRelationship) {
                    $read['fields'][] = $ruleRelationship->getFieldNameSource();
                }
            }

            return $read;
        }

        // Check every record haven't the same reference date
        // Make sure the next record hasn't the same date modified, so we delete at least the last one
        // This function run only when the limit call has been reached
        protected function validateReadDataSource(array $dataSource)
        {
            if (!empty($dataSource['values'])) {
                $dataSourceValues = $dataSource['values'];

                // Order data in the date_modified order
                $modified = array_column($dataSourceValues, 'date_modified');
                array_multisort($modified, SORT_DESC, $dataSourceValues);
                foreach ($dataSourceValues as $value) {
                    // Check if the previous record has the same date_modified than the current record
                    if (
                        empty($previousValue)   // first call
                        or (
                            !empty($previousValue['date_modified'])
                            and $previousValue['date_modified'] == $value['date_modified']
                        )
                    ) {
                        // Remove the current item, it will be read in the next call
                        unset($dataSource['values'][$value['id']]); // id equal the key in the dataSource table
                        --$dataSource['count'];
                        $previousValue = $value;
                        continue;
                    }
                    // Keep the reference date of the last record we have read
                    $dataSource['date_ref'] = $value['date_modified'];
                    break;
                }
                if (empty($dataSource['values'])) {
                    return ['error' => 'All records read have the same reference date in rule '.$this->rule['name'].'. Myddleware cannot garanty all data will be read. Job interrupted. Please increase the number of data read by changing the limit attribut in job and rule class.'];
                }
            }

            return true;
        }

        public function sendDocuments(array $documents = [], Job $job = null)
        {
            // creation into the target application
            $sendTarget = $this->sendTarget('C');
            // Update into the target application
            if (empty($sendTarget['error'])) {
                $sendTarget = $this->sendTarget('U');
            }
            // Deletion from the target application
            if (empty($sendTarget['error'])) {
                $sendTarget = $this->sendTarget('D');
            }
            // Logout target solution
            if (!empty($this->solutionTarget)) {
                $loginResult['error'] = $this->solutionTarget->logout();
                if (!$loginResult) {
                    $sendTarget['error'] .= 'Failed to logout from the target solution';
                }
            }

            return $sendTarget;
        }

        public function actionDocument(Document $document, $event, $param1 = null)
        {
            switch ($event) {
                case 'rerun':
                    return $this->rerun($document);
                    break;
                case 'cancel':
                    return $this->cancel($document);
                    break;
                case 'remove':
                    return $this->changeDeleteFlag($document, true);
                    break;
                case 'restore':
                    return $this->changeDeleteFlag($document, false);
                    break;
                case 'changeStatus':
                    return $this->changeStatus($document, $param1);
                    break;
                default:
                    return 'Action '.$event.' unknown. Failed to run this action. ';
            }
        }

        public function actionRule($event, Rule $rule = null)
        {
            switch ($event) {
                case 'ALL':
                    return $this->runMyddlewareJob('ALL');
                    break;
                case 'ERROR':
                    return $this->runMyddlewareJob('ERROR');
                    break;
                case 'runMyddlewareJob':
                    return $this->runMyddlewareJob($rule->getNameSlug());
                    break;
                default:
                    return 'Action '.$event.' unknown. Failed to run this action. ';
            }
        }

        // Permet de faire des contrôles dans Myddleware avant sauvegarde de la règle
        // Si le retour est false, alors la sauvegarde n'est pas effectuée et un message d'erreur est indiqué à l'utilisateur
        // data est de la forme :
        // [ruleName] => nom
        // [oldRule] => id de la règle précédente
        // [connector] => Array ( [source] => 3 [cible] => 30 )
        // [content] => Array (
        // [fields] => Array ( [name] => Array ( [Date] => Array ( [champs] => Array ( [0] => date_entered [1] => date_modified ) [formule] => Array ( [0] => {date_entered}.{date_modified} ) ) [account_Filter] => Array ( [champs] => Array ( [0] => name ) ) ) )
        // [params] => Array ( [mode] => 0 ) )
        // [relationships] => Array ( [0] => Array ( [target] => compte_Reference [rule] => 54ea64f1601fc [source] => Myddleware_element_id ) )
        // [module] => Array ( [source] => Array ( [solution] => sugarcrm [name] => Accounts ) [target] => Array ( [solution] => bittle [name] => oppt_multi7 ) )
        // La valeur de retour est de a forme : array('done'=>false, 'message'=>'message erreur');	ou array('done'=>true, 'message'=>'')
        public function beforeSave($data)
        {
            // Contrôle sur la solution source
            $solutionSource = $this->solutionManager->get($data['module']['source']['solution']);
            $check = $solutionSource->beforeRuleSave($data, 'source');
            // Si OK contôle sur la solution cible
            if ($check['done']) {
                $solutionTarget = $this->solutionManager->get($data['module']['target']['solution']);
                $check = $solutionTarget->beforeRuleSave($data, 'target');
            }

            return $check;
        }

        // Permet d'effectuer une action après la sauvegarde de la règle dans Myddleqare
        // Mêmes paramètres en entrée que pour la fonction beforeSave sauf que l'on a ajouté les entrées ruleId et date de référence au tableau
        public function afterSave($data)
        {
            // Contrôle sur la solution source
            $solutionSource = $this->solutionManager->get($data['module']['source']['solution']);
            $messagesSource = $solutionSource->afterRuleSave($data, 'source');

            $solutionTarget = $this->solutionManager->get($data['module']['target']['solution']);
            $messagesTarget = $solutionTarget->afterRuleSave($data, 'target');

            $messages = array_merge($messagesSource, $messagesTarget);
            $data['testMessage'] = '';
            // Affichage des messages
            if (!empty($messages)) {
                $session = new Session();
                foreach ($messages as $message) {
                    if ('error' == $message['type']) {
                        $errorMessages[] = $message['message'];
                    } else {
                        $successMessages[] = $message['message'];
                    }
                    $data['testMessage'] .= $message['type'].' : '.$message['message'].chr(10);
                }
                if (!empty($errorMessages)) {
                    $session->set('error', $errorMessages);
                }
                if (!empty($successMessages)) {
                    $session->set('success', $successMessages);
                }
            }
        }

        // Get all document of the rule
        protected function getRuleDocuments(Rule $rule, $withTarget = false)
        {
            $documentResult = [];
            $documents = $rule->getDocuments();
            if ($documents->count() > 0) {
                foreach ($documents as $document) {
                    $documentResult['sourceId'][$document->getSource()][] = $document;
                    if ($withTarget && !empty($document->getSource())) {
                        $documentResult['targetId'][$document->getTarget()][] = $document;
                    }
                }
            }

            return $documentResult;
        }

        // Permet de récupérer les règles potentiellement biderectionnelle.
        // Cette fonction renvoie les règles qui utilisent les même connecteurs et modules que la règle en cours mais en sens inverse (source et target inversées)
        // On est sur une méthode statique c'est pour cela que l'on récupère la connexion e paramètre et non dans les attributs de la règle
        public static function getBidirectionalRules($connection, $params)
        {
            try {
                // Récupération des règles opposées à la règle en cours de création
                $queryBidirectionalRules = 'SELECT 
											id, 
											name
										FROM Rule 
										WHERE 
												conn_id_source = :conn_id_target
											AND conn_id_target = :conn_id_source
											AND module_source = :module_target
											AND module_target = :module_source
											AND deleted = 0
										';
                $stmt = $connection->prepare($queryBidirectionalRules);
                $stmt->bindValue(':conn_id_source', $params['connector']['source']);
                $stmt->bindValue(':conn_id_target', $params['connector']['cible']);
                $stmt->bindValue(':module_source', $params['module']['source']);
                $stmt->bindValue(':module_target', $params['module']['cible']);
                $stmt->execute();
                $bidirectionalRules = $stmt->fetchAll();

                // Construction du tableau de sortie
                if (!empty($bidirectionalRules)) {
                    $option[''] = '';
                    foreach ($bidirectionalRules as $rule) {
                        $option[$rule['id']] = $rule['name'];
                    }
                    if (!empty($option)) {
                        return [
                            [
                                'id' => 'bidirectional',
                                'name' => 'bidirectional',
                                'required' => false,
                                'type' => 'option',
                                'label' => 'create_rule.step3.params.sync',
                                'option' => $option,
                            ],
                        ];
                    }
                }
            } catch (Exception $e) {
                return;
            }
        }

        // Permet d'annuler un docuement
        protected function cancel(Document $document)
        {
            $param['id_doc_myddleware'] = $document->getId();
            $param['jobId'] = $this->jobId;
            $param['api'] = $this->api;
            $doc = new document($this->logger, $this->container, $this->connection, $param);
            $doc->documentCancel();
            $session = new Session();
            $message = $doc->getMessage();

            // Si on a pas de jobId cela signifie que l'opération n'est pas massive mais sur un seul document
            // On affiche alors le message directement dans Myddleware
            if (empty($this->jobId)) {
                if (empty($message)) {
                    $session->set('success', ['Data transfer has been successfully cancelled.']);
                } else {
                    $session->set('error', [$doc->getMessage()]);
                }
            }
        }

        // Remove a document
        protected function changeDeleteFlag($id_document, $deleteFlag)
        {
            $param['id_doc_myddleware'] = $id_document;
            $param['jobId'] = $this->jobId;
            $param['api'] = $this->api;
            $doc = new document($this->logger, $this->container, $this->connection, $param);
            $doc->changeDeleteFlag($deleteFlag);
            $session = new Session();
            $message = $doc->getMessage();

            // Si on a pas de jobId cela signifie que l'opération n'est pas massive mais sur un seul document
            // On affiche alors le message directement dans Myddleware
            if (empty($this->jobId)) {
                if (empty($message)) {
                    $session->set('success', ['Data transfer has been successfully removed.']);
                } else {
                    $session->set('error', [$doc->getMessage()]);
                }
            }
        }

        // Remove a document
        protected function changeStatus($id_document, $toStatus)
        {
            $param['id_doc_myddleware'] = $id_document;
            $param['jobId'] = $this->jobId;
            $param['api'] = $this->api;
            $doc = new document($this->logger, $this->container, $this->connection, $param);
            $doc->updateStatus($toStatus);
        }

        protected function runMyddlewareJob($ruleSlugName)
        {
            try {
                $session = new Session();

                // create temp file
                $guid = uniqid();

                //get ..\bin\php\php.exe file
                $phpBinaryFinder = new PhpExecutableFinder();
                $phpBinaryPath = $phpBinaryFinder->find();
                $php = $phpBinaryPath;
    
                $fileTmp = $this->cacheDir.'/myddleware/job/'.$guid.'.txt';
                $fs = new Filesystem();
                try {
                    $fs->mkdir(dirname($fileTmp));
                } catch (IOException $e) {
                    throw new Exception($this->tools->getTranslation(['messages', 'rule', 'failed_create_directory']));
                }

                exec($php.' '.__DIR__.'/../../../../bin/console myddleware:synchro '.$ruleSlugName.' --env='.$this->env.' > '.$fileTmp.' &', $output);
                $cpt = 0;
                // Boucle tant que le fichier n'existe pas
                while (!file_exists($fileTmp)) {
                    if ($cpt >= 29) {
                        throw new Exception($this->tools->getTranslation(['messages', 'rule', 'failed_running_job']));
                    }
                    sleep(1);
                    ++$cpt;
                }

                // Boucle tant que l id du job n'est pas dans le fichier (écris en premier)
                $file = fopen($fileTmp, 'r');
                $firstLine = fgets($file);
                fclose($file);
                while (empty($firstLine)) {
                    if ($cpt >= 29) {
                        throw new Exception($this->tools->getTranslation(['messages', 'rule', 'failed_get_task_id']));
                    }
                    sleep(1);
                    $file = fopen($fileTmp, 'r');
                    $firstLine = fgets($file);
                    fclose($file);
                    ++$cpt;
                }

                // transform all information of the first line in an arry
                $result = explode(';', $firstLine);
                // Renvoie du message en session
                if ($result[0]) {
                    $session->set('info', ['<a href="'.$this->router->generate('task_view', ['id' => trim($result[1])]).'" target="blank_">'.$this->tools->getTranslation(['messages', 'rule', 'open_running_task']).'</a>.']);
                } else {
                    $session->set('error', [$result[1].(!empty($result[2]) ? '<a href="'.$this->router->generate('task_view', ['id' => trim($result[2])]).'" target="blank_">'.$this->tools->getTranslation(['messages', 'rule', 'open_running_task']).'</a>' : '')]);
                }

                return $result[0];
            } catch (Exception $e) {
                $session = new Session();
                $session->set('error', [$e->getMessage()]);

                return false;
            }
        }

        // Permet de relancer un document quelque soit son statut
        public function rerun(Document $document)
        {
            $msgError = [];
            $msgSuccess = [];
            $msgInfo = [];

            // Si la règle n'est pas chargée alors on l'initialise.
            $rule = $document->getRule();
            $mode = null;
            if ($rule) {
                $this->ruleRelationships = $rule->getRelationsShip();
                $ruleParams = [];
                foreach ($rule->getParams() as $ruleParam) {
                    $ruleParams[$ruleParam->getName()] = ltrim($ruleParam->getValue());
                }
                $this->sourceFields = $rule->getSourceFields();
                $this->targetFields = $rule->getTargetFields();
                $mode = $rule->getParamByName('mode');
            }

            $documentId = $document->getId();
            $status = $document->getStatus();
            $response = ['success' => false];
            // On lance des méthodes différentes en fonction du statut en cours du document et en fonction de la réussite ou non de la fonction précédente
            if (in_array($document->getStatus(), ['New', 'Filter_KO'])) {
                $response = $this->document->filterDocument($document);
                if (true === $response['success']) {
                    $msgSuccess[] = 'Transfer id '.$documentId.' : Status change => Filter_OK';
                } elseif (-1 === $response['success']) {
                    $msgInfo[] = 'Transfer id '.$documentId.' : Status change => Filter';
                } else {
                    $msgError[] = 'Transfer id '.$documentId.' : Error, status transfer => Filter_KO';
                }
            }
            if (true === $response['success'] || in_array($status, ['Filter_OK', 'Predecessor_KO'])) {
                $response = $this->document->ckeckPredecessorDocument($document);
                if (true === $response['success']) {
                    $msgSuccess[] = 'Transfer id '.$documentId.' : Status change => Predecessor_OK';
                } else {
                    $msgError[] = 'Transfer id '.$documentId.' : Error, status transfer => Predecessor_KO';
                }
            }
            if (true === $response['success'] || in_array($status, ['Predecessor_OK', 'Relate_KO'])) {
                $response = $this->ckeckParentDocuments([['id' => $documentId]]);
                if (true === $response['success']) {
                    $msgSuccess[] = 'Transfer id '.$documentId.' : Status change => Relate_OK';
                } else {
                    $msgError[] = 'Transfer id '.$documentId.' : Error, status transfer => Relate_KO';
                }
            }
            if (true === $response[$documentId] || in_array($status, ['Relate_OK', 'Error_transformed'])) {
                $response = $this->transformDocuments($document);
                if (true === $response['success']) {
                    $msgSuccess[] = 'Transfer id '.$documentId.' : Status change : Transformed';
                } else {
                    $msgError[] = 'Transfer id '.$documentId.' : Error, status transfer : Error_transformed';
                }
            }
            if (true === $response['success'] || in_array($status, ['Transformed', 'Error_checking', 'Not_found'])) {
                $response = $this->getTargetDataDocuments($document);
                if (true === $response['success']) {
                    if ('S' == $mode) {
                        $msgSuccess[] = 'Transfer id '.$documentId.' : Status change : '.$response['doc_status'];
                    } else {
                        $msgSuccess[] = 'Transfer id '.$documentId.' : Status change : '.$response['doc_status'];
                    }
                } else {
                    $msgError[] = 'Transfer id '.$documentId.' : Error, status transfer : '.$response['doc_status'];
                }
            }
            // Si la règle est en mode recherche alors on n'envoie pas de données
            // Si on a un statut compatible ou si le doc vient de passer dans l'étape précédente et qu'il n'est pas no_send alors on envoie les données
            if (
                'S' != $mode
                && (
                    in_array($status, ['Ready_to_send', 'Error_sending'])
                    || (
                        true === $response['success']
                        && (
                            empty($response['doc_status'])
                            || (
                                !empty($response['doc_status'])
                                && 'No_send' != $response['doc_status']
                            )
                        )
                    )
                )
            ) {
                $response = $this->sendTarget('', $rule, $document);
                if (
                    !empty($response[$document->getId()]['id'])
                    && empty($response[$document->getId()]['error'])
                    && empty($response['error']) // Error can be on the document or can be a general error too
                ) {
                    $msgSuccess[] = 'Transfer id '.$document->getId().' : Status change : Send';
                } else {
                    $msgError[] = 'Transfer id '.$document->getId().' : Error, status transfer : Error_sending. '.(!empty($response['error']) ? $response['error'] : $response[$document->getId()]['error']);
                }
            }

            // If the job is manual, we display error in the UI
            if ($this->manual) {
                if (!empty($msgError)) {
                    $this->session->set('error', $msgError);
                }
                if (!empty($msgSuccess)) {
                    $this->session->set('success', $msgSuccess);
                }
                if (!empty($msgInfo)) {
                    $this->session->set('info', $msgInfo);
                }
            }

            return $msgError;
        }

        protected function clearSendData($sendData)
        {
            if (!empty($sendData)) {
                foreach ($sendData as $key => $value) {
                    unset($value['source_date_modified']);
                    unset($value['id_doc_myddleware']);
                    $sendData[$key] = $value;
                }
            }

            return $sendData;
        }

        protected function sendTarget($type, Rule $rule, Document $document = null)
        {
            try {
                // Permet de charger dans la classe toutes les relations de la règle
                $response = [];
                $response['error'] = '';

                // Le type peut-être vide das le cas d'un relancement de flux après une erreur
                if (empty($type)) {
                    $type = $document->getType();
                }

                // Récupération du contenu de la table target pour tous les documents à envoyer à la cible
                $send['data'] = $this->getSendDocuments($rule, $type, $document);
                $send['module'] = $rule->getModuleTarget();
                $send['ruleId'] = $rule->getId();
                $send['rule'] = $rule;
                $send['ruleFields'] = $rule->getFields();
                $send['ruleParams'] = $rule->getParamsValues();
                $send['ruleRelationships'] = $rule->getRelationsShip();
                $send['jobId'] = $job->getId();
                // Si des données sont prêtes à être créées
                if (!empty($send['data'])) {
                    // If the rule is a child rule, no document is sent. They will be sent with the parent rule.
                    if ($rule->isChild()) {
                        foreach ($send['data'] as $key => $data) {
                            // True is send to avoid an error in rerun method. We should put the target_id but the document will be send with the parent rule.
                            $response[$key] = ['id' => true];
                        }

                        return $response;
                    }

                    // Connexion à la cible
                    $connect = $this->connexionSolution($rule, 'target');
                    if (true === $connect) {
                        // Création des données dans la cible
                        if ('C' == $type) {
                            // Permet de vérifier que l'on ne va pas créer un doublon dans la cible
                            $send['data'] = $this->checkDuplicate($send['data']);
                            $send['data'] = $this->clearSendData($send['data']);
                            $response = $this->solutionTarget->create($send);
                        } // Modification des données dans la cible
                        elseif ('U' == $type) {
                            $send['data'] = $this->clearSendData($send['data']);
                            // permet de récupérer les champ d'historique, nécessaire pour l'update de SAP par exemple
                            $send['dataHistory'] = $this->getSendDocuments($type, $rule, $document, 'history');
                            $send['dataHistory'] = $this->clearSendData($send['dataHistory']);
                            $response = $this->solutionTarget->update($send);
                        } // Delete data from target application
                        elseif ('D' == $type) {
                            $response = $this->solutionTarget->delete($send);
                        } else {
                            $response[$document->getId()] = false;
                            $response['error'] = 'Type transfer '.$type.' unknown. ';
                        }
                    } else {
                        $response[$document->getId()] = false;
                        $response['error'] = $connect['error'];
                    }
                }
            } catch (Exception $e) {
                $response['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                if (!$this->api) {
                    echo $response['error'];
                }
                $this->logger->error($response['error']);
            }

            return $response;
        }

        protected function checkDuplicate($transformedData)
        {
            // Traitement si présence de champ duplicate
            if (empty($this->ruleParams['duplicate_fields'])) {
                return $transformedData;
            }
            $duplicate_fields = explode(';', $this->ruleParams['duplicate_fields']);
            $searchDuplicate = [];
            // Boucle sur chaque donnée qui sera envoyée à la cible
            foreach ($transformedData as $docId => $rowTransformedData) {
                // Stocke la valeur des champs duplicate concaténée
                $concatduplicate = '';

                // Récupération des valeurs de la source pour chaque champ de recherche
                foreach ($duplicate_fields as $duplicate_field) {
                    $concatduplicate .= $rowTransformedData[$duplicate_field];
                }
                // Empty data aren't used for duplicate search
                if (!empty(trim($concatduplicate))) {
                    $searchDuplicate[$docId] = ['concatKey' => $concatduplicate, 'source_date_modified' => $rowTransformedData['source_date_modified']];
                }
            }

            // Recherche de doublons dans le tableau searchDuplicate
            if (!empty($searchDuplicate)) {
                // Obtient une liste de colonnes
                foreach ($searchDuplicate as $key => $row) {
                    $concatKey[$key] = $row['concatKey'];
                    $source_date_modified[$key] = $row['source_date_modified'];
                }

                // Trie les données par volume décroissant, edition croissant
                // Ajoute $data en tant que dernier paramètre, pour trier par la clé commune
                array_multisort($concatKey, SORT_ASC, $source_date_modified, SORT_ASC, $searchDuplicate);

                // Si doublon charge on charge les documents doublons, on récupère les plus récents et on les passe à transformed sans les envoyer à la cible.
                // Le plus ancien est envoyé.
                $previous = '';
                foreach ($searchDuplicate as $key => $value) {
                    if (empty($previous)) {
                        $previous = $value['concatKey'];
                        continue;
                    }
                    // Si doublon
                    if ($value['concatKey'] == $previous) {
                        $param['id_doc_myddleware'] = $key;
                        $param['jobId'] = $this->jobId;
                        $param['api'] = $this->api;
                        $doc = new document($this->logger, $this->container, $this->connection, $param);
                        $doc->setMessage('Failed to send document because this record is already send in another document. To prevent create duplicate data in the target system, this document will be send in the next job.');
                        $doc->setTypeError('W');
                        $doc->updateStatus('Transformed');
                        // Suppression du document dans l'envoi
                        unset($transformedData[$key]);
                    }
                    $previous = $value['concatKey'];
                }
            }
            if (!empty($transformedData)) {
                return $transformedData;
            }
        }

        protected function getSendDocuments($type, Rule $rule, Document $document = null, $table = 'target', Document $parentDoc = null, $parentRuleId = '')
        {
            // Sélection de tous les documents au statut transformed en attente de création pour la règle en cours
            // Si un document est en paramètre alors on filtre la requête sur le document
            if (null === $document) {
                if (!empty($parentDocId)) {
                    $documents = $this->documentRepository->findBy(['parentId' => $parentDoc->getId(), 'rule' => $parentRuleId], ['sourceDateModified' => 'ASC']);
                } // Sinon on récupère tous les documents élligible pour l'envoi
                else {
                    $documents = $this->documentRepository->findBy(['rule' => $rule, 'deleted' => false, 'status' => 'Ready_to_send', 'type' => $type], ['sourceDateModified' => 'ASC']);
                }
            } else {
                $documents = [$document];
            }

            $return = [];
            foreach ($documents as $document) {
                // If the rule is a parent, we have to get the data of all rules child
                $childRulesRelationship = $rule->getChildRules();
                if ($childRulesRelationship->count()) {
                    foreach ($childRulesRelationship as $childRuleRelationship) {
                        $ruleChild = $this->ruleRepository->find($childRuleRelationship->getFieldId());
                        $ruleChildParam['jobId'] = $this->jobId;
                        $dataChild = $this->getSendDocuments('U', $ruleChild, null, $table, $document, $ruleChild);
                        // Store the submodule data to be send in the parent document
                        // If the structure already exists in the document array, we merge data (several rules can add dsata in the same structure)
                        if (empty($document[$ruleChild->getModuleTarget()])) {
                            $document[$ruleChild->getModuleTarget()] = $dataChild;
                        } else {
                            if (!empty($dataChild)) {
                                $document[$ruleChild->getModuleTarget()] = array_merge($document[$ruleChild->getModuleTarget()], $dataChild);
                            }
                        }
                    }
                }
                $data = $document->getDataByType('T');
                if (!empty($data)) {
                    $return[$document->getId()] = array_merge($document, $data);
                }
            }

            return $return;
        }

        // Fonction permettant de définir un ordre dans le lancement des règles
        public function orderRules()
        {
            // Récupération de toutes les règles avec leurs règles liées (si plusieurs elles sont toutes au même endroit)
            // Si la règle n'a pas de relation on initialise l'ordre à 1 sinon on met 99
            $rules = $this->ruleRepository->getRuleToOrder();
            $rulesRef = [];
            if (!empty($rules)) {
                // Création d'un tableau en clé valeur et sauvegarde d'un tableau de référence
                $ruleKeyValue = [];
                foreach ($rules as $key => $rule) {
                    // Init order depending on the field_id value
                    if (empty($rule['field_id'])) {
                        $rules[$key]['rule_order'] = 1;
                    } else {
                        $rules[$key]['rule_order'] = 99;
                    }
                    $ruleKeyValue[$rule['id']] = $rules[$key]['rule_order'];
                    $rulesRef[$rule['id']] = $rule; 
                    $rulesObject[$rule['id']] = $rule;            
                }

                // On calcule les priorité tant que l'on a encore des priorité 99
                // On fait une condition sur le $i pour éviter une boucle infinie
                $i = 0;
                while ($i < 20 && false !== array_search('99', $ruleKeyValue)) {
                    ++$i;
                    // Boucles sur les régles
                    foreach ($rules as $rule) {
                        $order = 0;
                        // Si on est une règle sans ordre
                        if (
                            !empty($rule['rule_order'])
                            and '99' == $rule['rule_order']
                        ) {
                            // Récupération des règles liées et recherche dans le tableau keyValue
                            $rulesLink = explode(';', $rule['field_id']);
                            foreach ($rulesLink as $ruleLink) {
                                if (
                                    !empty($ruleKeyValue[$ruleLink])
                                    && $ruleKeyValue[$ruleLink] > $order
                                ) {
                                    $order = $ruleKeyValue[$ruleLink];
                                }
                            }
                            // Si toutes les règles trouvées ont une priorité autre que 99 alors on affecte à la règle la piorité +1 dans les tableaux de références
                            if ($order < 99) {
                                $ruleKeyValue[$rule['id']] = $order + 1;
                                $rulesRef[$rule['id']]['rule_order'] = $order + 1;
                            }
                        }
                    }
                    $rules = $rulesRef;
                }

                // On vide la table RuleOrder
                $this->ruleOrderRepository->deleteAll();  
      
                foreach ($ruleKeyValue as $key => $value) {
                   //For now, we need to use SQL request because the keyword 'order' is a reserved SQL word
                   // and when using Doctrine persist/flush, it creates an error as it doesn't use `order`
                    $ruleToSort = $this->ruleRepository->find($key);
                    $ruleId = $ruleToSort->getId();
                    $sqlInsert = "INSERT INTO `ruleorder` (`rule_id`, `order`) VALUES ( '$ruleId', '$value');";
                    $sqlInsert = $this->connection->prepare($sqlInsert);
                    $sqlInsert->execute();
                    // $newRuleOrder = new RuleOrder();
                    // $newRuleOrder
                    //     ->setRule($ruleToSort)
                    //     ->setOrder($value);
                    // $this->entityManager->persist($newRuleOrder);
                }
                // $this->entityManager->flush();
            }

            return ['success' => true];
        }

        // Parametre de la règle choix utilisateur
        /*
        array(
            'id' 		=> 'datereference',
            'name' 		=> 'datereference',
            'required'	=> true,
            'type'		=> 'text',
            'label' => 'solution.params.dateref',
            'readonly' => true
        ),	*/
        public static function getFieldsParamUpd()
        {
            return [];
        }

        // Parametre de la règle obligation du système par défaut
        public static function getFieldsParamDefault($idSolutionSource = '', $idSolutionTarget = '')
        {
            return [
                'active' => false,
                'RuleParam' => [
                    'limit' => '100',
                    'delete' => '60',
                    'datereference' => date('Y-m-d').' 00:00:00',
                ],
            ];
        }

        // Parametre de la règle en modification dans la fiche
        public static function getFieldsParamView()
        {
            return [
                [
                    'id' => 'datereference',
                    'name' => 'datereference',
                    'required' => true,
                    'type' => TextType::class,
                    'label' => 'solution.params.dateref',
                ],
                [
                    'id' => 'limit',
                    'name' => 'limit',
                    'required' => true,
                    'type' => IntegerType::class,
                    'label' => 'solution.params.limit',
                ],
                [ // clear data
                    'id' => 'delete',
                    'name' => 'delete',
                    'required' => false,
                    'type' => 'option',
                    'label' => 'solution.params.delete',
                    'option' => [
                        '0' => 'solution.params.0_day',
                        '1' => 'solution.params.1_day',
                        '7' => 'solution.params.7_day',
                        '14' => 'solution.params.14_day',
                        '30' => 'solution.params.30_day',
                        '60' => 'solution.params.60_day',
                    ],
                ],
                [
                    'id' => 'description',
                    'name' => 'description',
                    'required' => true,
                    'type' => TextareaType::class,
                    'label' => 'solution.params.description',
                ],
            ];
        }
    }
}
