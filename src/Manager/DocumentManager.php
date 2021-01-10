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

use App\Entity\Document;
use App\Entity\DocumentData;
use App\Entity\DocumentData as DocumentDataEntity;
use App\Entity\DocumentRelationship as DocumentRelationship;
// Gestion des logs
// Accède aux services
// Connexion BDD
use App\Entity\Job;
use App\Entity\Log;
use App\Entity\Rule;
use App\Manager\myddlewareFormulaV1 as Formule; // SugarCRM Myddleware
use App\Repository\DocumentRepository;
use App\Repository\RuleRelationShipRepository;
use DateTime;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

$file = __DIR__.'/../Custom/Manager/DocumentManager.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class DocumentManager
    {
        const lstStatus = [
            'New' => 'flux.status.new',
            'Predecessor_OK' => 'flux.status.predecessor_ok',
            'Relate_OK' => 'flux.status.relate_ok',
            'Transformed' => 'flux.status.transformed',
            'Ready_to_send' => 'flux.status.ready_to_send',
            'Filter_OK' => 'flux.status.filter_ok',
            'Send' => 'flux.status.send',
            'Filter' => 'flux.status.filter',
            'No_send' => 'flux.status.no_send',
            'Cancel' => 'flux.status.cancel',
            'Filter_KO' => 'flux.status.filter_ko',
            'Predecessor_KO' => 'flux.status.predecessor_ko',
            'Relate_KO' => 'flux.status.relate_ko',
            'Error_transformed' => 'flux.status.error_transformed',
            'Error_checking' => 'flux.status.error_checking',
            'Error_sending' => 'flux.status.error_sending',
        ];

        const lstGblStatus = [
            'Open' => 'flux.gbl_status.open',
            'Close' => 'flux.gbl_status.close',
            'Cancel' => 'flux.gbl_status.cancel',
            'Error' => 'flux.gbl_status.error',
        ];

        const lstType = [
            'C' => 'flux.type.create',
            'U' => 'flux.type.update',
            'D' => 'flux.type.delete',
            'S' => 'flux.type.search',
        ];
        public $id;

        protected $entityManager;
        protected $typeError = 'S';
        protected $message = '';
        protected $dateCreated;
        protected $connection;
        protected $ruleName;
        protected $ruleMode;
        protected $ruleId;
        protected $ruleFields;
        protected $ruleRelationships;
        protected $ruleParams;
        protected $sourceId;
        protected $targetId;
        protected $parentId;
        protected $sourceData;
        protected $data;
        protected $documentType;
        protected $jobActive = true;
        protected $attempt;
        protected $userId;
        protected $status;
        protected $document_data;
        protected $solutionTarget;
        protected $solutionSource;
        protected $jobId;
        protected $key;
        protected $docIdRefError;
        protected $transformError = false;
        protected $tools;
        protected $api;    // Specify if the class is called by the API
        protected $ruleDocuments;
        protected $globalStatus = [
            'New' => 'Open',
            'Predecessor_OK' => 'Open',
            'Relate_OK' => 'Open',
            'Transformed' => 'Open',
            'Ready_to_send' => 'Open',
            'Filter_OK' => 'Open',
            'Send' => 'Close',
            'Found' => 'Close',
            'Filter' => 'Cancel',
            'No_send' => 'Cancel',
            'Cancel' => 'Cancel',
            'Filter_KO' => 'Error',
            'Predecessor_KO' => 'Error',
            'Relate_KO' => 'Error',
            'Error_transformed' => 'Error',
            'Error_checking' => 'Error',
            'Error_sending' => 'Error',
            'Not_found' => 'Error',
        ];

        protected $container;
        protected $logger;
        /**
         * @var myddlewareFormulaV1core
         */
        private $formule;
        /**
         * @var DocumentRepository
         */
        private $documentRepository;
        /**
         * @var RuleRelationShipRepository
         */
        private $ruleRelationshipsRepository;

        // Instanciation de la classe de génération de log Symfony
        public function __construct(
            LoggerInterface $logger,
            Connection $dbalConnection,
            EntityManagerInterface $entityManager,
            DocumentRepository $documentRepository,
            RuleRelationShipRepository $ruleRelationshipsRepository,
            ParameterBagInterface $params,
            ToolsManager $tools,
            FormulaManager $formule
        ) {
            $this->connection = $dbalConnection;
            $this->logger = $logger;
            $this->entityManager = $entityManager;
            $this->documentRepository = $documentRepository;
            $this->ruleRelationshipsRepository = $ruleRelationshipsRepository;
            $param = $params->get('param');
            $this->tools = $tools;
            $this->formule = $formule;

            // Chargement des solution si elles sont présentent dans les paramètres de construction
            if (!empty($param['solutionTarget'])) {
                $this->solutionTarget = $param['solutionTarget'];
            }
            if (!empty($param['solutionSource'])) {
                $this->solutionSource = $param['solutionSource'];
            }
            if (!empty($param['jobId'])) {
                $this->jobId = $param['jobId'];
            }
            if (!empty($param['api'])) {
                $this->api = $param['api'];
            }
            if (!empty($param['key'])) {
                $this->key = $param['key'];
            }
            if (!empty($param['ruleDocuments'])) {
                $this->ruleDocuments = $param['ruleDocuments'];
            }

            // If mode isn't front ofice => only when the user click on "Simulation" during the rule creation
            if (
                empty($param['mode'])
                || (
                    !empty($param['mode'])
                    && 'front_office' != $param['mode']
                )
            ) {
                // Init attribut of the class Document
                if (!empty($param['id_doc_myddleware'])) {
                    // Instanciate attribut sourceData
                    $this->setDocument($param['id_doc_myddleware']);
                } else {
                    $this->id = uniqid('', true);
                    $this->dateCreated = gmdate('Y-m-d H:i:s');
                    $this->ruleName = $param['rule']['name_slug'];
                    $this->ruleMode = $param['rule']['mode'];
                    $this->ruleId = $param['rule']['id'];
                    $this->ruleFields = $param['ruleFields'];
                    $this->data = $param['data'];
                    $this->sourceId = $this->data['id'];
                    $this->userId = $param['rule']['created_by'];
                    $this->status = 'New';
                    $this->attempt = 0;
                    // Set the deletion type if myddleware deletion flag is true
                    if (!empty($this->data['myddleware_deletion'])) {
                        $this->documentType = 'D';
                    }
                }
                // Ajout des paramètre de la règle
                $this->setRuleParam();
            }
            // Mise à jour des tableaux s'ils existent.
            if (!empty($param['ruleFields'])) {
                $this->ruleFields = $param['ruleFields'];
            }
            if (!empty($param['ruleRelationships'])) {
                $this->ruleRelationships = $param['ruleRelationships'];
            }
        }

        public function setDocument($id_doc)
        {
            try {
                $sqlParams = "	SELECT 
								Document.*, 
								Rule.name_slug,
								RuleParam.value mode,
								Rule.conn_id_source,
								Rule.module_source
							FROM Document 
								INNER JOIN Rule
									ON Document.rule_id = Rule.id
								INNER JOIN RuleParam
									ON  RuleParam.rule_id = Rule.id
									AND RuleParam.name= 'mode'
							WHERE Document.id = :id_doc";
                $stmt = $this->connection->prepare($sqlParams);
                $stmt->bindValue(':id_doc', $id_doc);
                $stmt->execute();
                $this->document_data = $stmt->fetch();

                if (!empty($this->document_data['id'])) {
                    $this->id = $this->document_data['id'];
                    $this->dateCreated = $this->document_data['date_created'];
                    $this->userId = $this->document_data['created_by'];
                    $this->ruleId = $this->document_data['rule_id'];
                    $this->status = $this->document_data['status'];
                    $this->sourceId = $this->document_data['source_id'];
                    $this->targetId = $this->document_data['target_id'];
                    $this->ruleName = $this->document_data['name_slug'];
                    $this->ruleMode = $this->document_data['mode'];
                    $this->documentType = $this->document_data['type'];
                    $this->attempt = $this->document_data['attempt'];

                    // Get source data and create data attribut
                    $this->sourceData = $this->getDocumentData('S');
                    $this->data = $this->sourceData;
                    // Get document header
                    $documentEntity = $this->entityManager->getRepository(\App\Entity\Document::class)->find($id_doc);
                    $this->data['id'] = $documentEntity->getSource();
                    $this->data['source_date_modified'] = $documentEntity->getSourceDateModified()->format('Y-m-d H:i:s');
                } else {
                    $this->logger->error('Failed to retrieve Document '.$id_doc.'.');
                }
            } catch (Exception $e) {
                $this->message .= 'Failed to retrieve document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
                $this->createDocLog();
            }
        }

        public function createDocument(Rule $rule, array $row)
        {
            // On ne fait pas de beginTransaction ici car on veut pouvoir tracer ce qui a été fait ou non. Si le créate n'est pas du tout fait alors les données sont perdues
            // L'enregistrement même partiel d'un document nous permet de tracer l'erreur.
            try {
                // Return false if job has been manually stopped
                if (!$this->jobActive) {
                    $this->message .= 'Job is not active. ';

                    return false;
                }

                $modeParam = $rule->getParamByName('mode');
                $newDocument = new Document();
                $newDocument
                    ->setRule($rule)
                    ->setDateCreated(new DateTime())
                    ->setDateModified(new DateTime())
                    ->setCreatedBy($rule->getCreatedBy())
                    ->setModifiedBy($rule->getCreatedBy())
                    ->setSource($rule->getConnectorSource()->getId())
                    ->setSourceDateModified(new DateTime())
                    ->setMode($modeParam ? $modeParam->getId() : null)
                    ->setParentId($row['parent_id'] ?? null)
                ;

                // Création du header de la requête
                $this->updateStatus($newDocument, 'New');
                // Insert source data
                return $this->insertDataTable($newDocument, $row, 'S');
            } catch (Exception $e) {
                $message = 'Failed to create document (id source : '.$this->sourceId.'): '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($message);

                return ['success' => false, 'message' => $message];
            }
        }

        // Permet d'indiquer si le filtreest rempli ou pas
        protected function checkFilter($fieldValue, $operator, $filterValue)
        {
            switch ($operator) {
                case 'content':
                    $pos = stripos($fieldValue, $filterValue);
                    if (false === $pos) {
                        return false;
                    }

                    return true;

                    break;
                case 'notcontent':
                    $pos = stripos($fieldValue, $filterValue);
                    if (false === $pos) {
                        return true;
                    }

                    return false;

                    break;
                case 'begin':
                    $begin = substr($fieldValue, 0, strlen($filterValue));
                    if (strtoupper($begin) == strtoupper($filterValue)) {
                        return true;
                    }

                    return false;

                    break;
                case 'end':
                    $begin = substr($fieldValue, 0 - strlen($filterValue));
                    if (strtoupper($begin) == strtoupper($filterValue)) {
                        return true;
                    }

                    return false;

                    break;
                case 'in':
                    if (in_array(strtoupper($fieldValue), explode(';', strtoupper($filterValue)))) {
                        return true;
                    }

                    return false;

                    break;
                case 'notin':
                    if (!in_array(strtoupper($fieldValue), explode(';', strtoupper($filterValue)))) {
                        return true;
                    }

                    return false;

                    break;
                case 'gt':
                    if ($fieldValue > $filterValue) {
                        return true;
                    }

                    return false;

                    break;
                case 'lt':
                    if ($fieldValue < $filterValue) {
                        return true;
                    }

                    return false;

                    break;
                case 'lteq':
                    if ($fieldValue <= $filterValue) {
                        return true;
                    }

                    return false;

                    break;
                case 'gteq':
                    if ($fieldValue >= $filterValue) {
                        return true;
                    }

                    return false;

                    break;
                case 'equal':
                    if (strtoupper($fieldValue) == strtoupper($filterValue)) {
                        return true;
                    }

                    return false;

                    break;
                case 'different':
                    if (strtoupper($fieldValue) != strtoupper($filterValue)) {
                        return true;
                    }

                    return false;

                    break;
                default:
                    $this->message .= 'Failed to filter. Operator '.$operator.' unknown. ';

                    return false;
            }
        }

        public function filterDocuments(array $documents = [], Job $job = null)
        {
            // Pour tous les documents sélectionnés on vérifie les prédécesseurs
            /** @var Document $document */
            foreach ($documents as $document) {
                $this->filterDocument($document, $job);
            }
        }

        // Permet de filtrer ou non un document
        public function filterDocument(Document $document, Job $job = null)
        {
            // Return false if job has been manually stopped
            if (null !== $job && 'Start' !== $job->getStatus()) {
                return ['success' => false, 'message' => 'Job is not active.'];
            }

            try {
                $filterOK = true;
                $message = '';
                $rule = $document->getRule();
                $ruleFilters = $rule->getFilters();
                // Si des filtres sont présents
                if ($ruleFilters->count() > 0) {
                    // Boucle sur les filtres
                    foreach ($ruleFilters as $ruleFilter) {
                        $sourceData = $rule->getDocumentsByStatus('S');
                        if (!$this->checkFilter($sourceData[$ruleFilter->getTarget()], $ruleFilter->getType(), $ruleFilter->getValue())) {
                            $message = 'This document is filtered. This operation is false : '.$ruleFilter['target'].' '.$ruleFilter['type'].' '.$ruleFilter['value'].'.';
                            $this->updateStatus($document, 'Filter', $this->api, null, $message);
                            $filterOK = -1;
                            break;
                        }
                    }
                }
                // Si on a pas eu d'erreur alors le document passe à l'étape suivante
                if (true === $filterOK) {
                    $this->updateStatus($document, 'Filter_OK', $this->api);
                }

                return ['success' => $filterOK, 'message' => $message];
            } catch (Exception $e) {
                $message = 'Failed to filter document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $typeError = 'E';
                $this->updateStatus($document, 'Filter_KO', $this->api, $typeError);
                $this->logger->error($message);

                return ['success' => false, 'message' => $message];
            }
        }

        // Permet de contrôler si un document de la même règle pour le même enregistrement n'est pas close
        // Si un document n'est pas clos alors le statut du docuement est mis à "pending"
        public function checkPredecessorDocuments(array $documents = [], Job $job = null)
        {
            // Pour tous les docuements sélectionnés on vérifie les prédécesseurs
            /** @var Document $document */
            foreach ($documents as $document) {
                $this->checkPredecessorDocument($document, $job);
            }
        }

        // Permet de valider qu'aucun document précédent pour la même règle et le même id sont bloqués
        public function checkPredecessorDocument(Document $document, Job $job = null)
        {
            // Return false if job has been manually stopped
            if (null !== $job && 'Start' !== $job->getStatus()) {
                return ['success' => false, 'message' => 'Job is not active.'];
            }

            try {
                $rule = $document->getRule();
                // if id found, we stop to send an error
                if (in_array($document->getGlobalStatus(), ['Error', 'Open'])) {
                    throw new Exception('The document '.$document->getId().' is on the same record and is not closed. This document is queued. ');
                }

                $bidirectionalParam = $rule->getParamByName('bidirectional');
                // Check predecessor in the opposite bidirectional rule
                if ($bidirectionalParam) {
                    $bidirectionalDocument = $this->documentRepository->findDocumentByFilters([
                        'rule' => $bidirectionalParam->getValue(),
                        'source' => $document->getSource(),
                        'dateCreated' => $document->getDateCreated(),
                        'deleted' => false,
                        'globalStatus' => ['Error', 'Open'],
                    ]);
                    if ($bidirectionalDocument instanceof Document) {
                        throw new Exception('The document '.$document->getId().' is on the same record on the bidirectional rule '.$bidirectionalDocument->getId().'. This document is not closed. This document is queued. ');
                    }
                }

                // Check predecessor in the child rule
                // Get all child rules
                $childRules = $this->ruleRelationshipsRepository->findDocumentChildsRules($rule);
                if (count($childRules)) {
                    // If rule child, document open in ready_to_send are accepted because data in ready to send could be pending
                    foreach ($childRules as $childRule) {
                        $childRuleDocument = $this->documentRepository->findDocumentByReadyToSend([
                            'rule' => $childRule->getRule(),
                            'source' => $document->getSource(),
                            'dateCreated' => $document->getDateCreated(),
                            'deleted' => false,
                        ]);

                        if ($childRuleDocument instanceof Document) {
                            throw new Exception('The document '.$childRuleDocument->getId().' is on the same record on the bidirectional rule '.$childRule->getRule()->getId().'. This document is not closed. This document is queued. ');
                        }
                    }
                }

                // Get the target id and the type of the document
                $type_document = $this->checkRecordExist($document->getSource());

                // Don't change the document type if the type is deletion
                if ('D' != $this->documentType) {
                    $this->documentType = $type_document;
                    // Override the document type in case of search type rule
                    if ('S' == $this->ruleMode) {
                        $this->documentType = 'S';
                    }
                    // Update the type of the document
                    if (empty($this->documentType)) {
                        throw new Exception('Failed to find a type for this document. ');
                    }
                    $this->updateType($this->documentType);
                }

                // Update the target ID if we found it (target Id is required for update and deletion)
                if (
                    (
                        'U' == $this->documentType
                        or 'D' == $this->documentType
                    )
                    and !$this->isChild()
                ) {
                    if (empty($this->targetId)) {
                        // If no predecessor at all (even in error or open) and type D => it means that Myddleware has never sent the record so we can't delete it
                        if ('D' == $this->documentType) {
                            $this->message .= 'No predecessor. Myddleware has never sent this record so it cannot delete it. This data transfer is cancelled. ';
                            $this->updateStatus('Cancel');
                            $this->connection->commit(); // -- COMMIT TRANSACTION

                            return false;
                        }
                        throw new Exception('No target id found for a document with the type Update. ');
                    }
                    if (!$this->updateTargetId($this->targetId)) {
                        throw new Exception('Failed to update the target id. Failed to unblock this update document. ');
                    }
                }

                // Set the status Predecessor_OK
                $this->updateStatus('Predecessor_OK');

                // Check compatibility between rule mode et document tupe
                // A rule in create mode can't update data excpt for a child rule
                if (
                    'C' == $this->ruleMode
                    and 'U' == $this->documentType
                    and !$this->isChild()
                ) {
                    $this->message .= 'Rule mode only allows to create data. Filter because this document updates data.';
                    $this->updateStatus('Filter');
                    // In case we flter the document, we return false to stop the process when this method is called in the rerun process
                    $this->connection->commit(); // -- COMMIT TRANSACTION

                    return false;
                }
                $this->connection->commit(); // -- COMMIT TRANSACTION

                return true;
            } catch (Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                // Reference document id is used to show which document is blocking the current document in Myddleware
                $this->docIdRefError = (!empty($result['id']) ? $result['id'] : '');
                $this->message .= 'Failed to check document predecessor : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->updateStatus('Predecessor_KO');
                $this->logger->error($this->message);

                return false;
            }
        }

        // Permet de contrôler si un document de la même règle pour le même enregistrement n'est pas close
        // Si un document n'est pas clos alors le statut du docuement est mis à "pending"
        public function checkParentDocuments($documents = null)
        {
            // Permet de charger dans la classe toutes les relations de la règle
            $response = [];

            // Sélection de tous les documents de la règle au statut 'New' si aucun document n'est en paramètre
            if (empty($documents)) {
                $documents = $rule->getDocumentsByStatus('Predecessor_OK');
            }
            if (!empty($documents)) {
                $param['jobId'] = $this->jobId;
                $param['ruleRelationships'] = $this->ruleRelationships;
                // If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->getTargetId
                $migrationParameters = $this->params->get('migration');
                if (!empty($migrationParameters['mode'])) {
                    if (!empty($this->ruleRelationships)) {
                        // Get all documents of every rules linked
                        foreach ($this->ruleRelationships as $ruleRelationship) {
                            // Get documents only if we don't have them yet (we could have several relationship to the same rule)
                            if (empty($param['ruleDocuments'][$ruleRelationship['field_id']])) {
                                $param['ruleDocuments'][$ruleRelationship['field_id']] = $this->getRuleDocuments($ruleRelationship['field_id'], true);
                            }
                        }
                    }
                }
                // Pour tous les docuements sélectionnés on vérifie les parents
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    $param['jobId'] = $this->jobId;
                    $param['api'] = $this->api;
                    $param['ruleRelationships'] = $this->ruleRelationships;
                    $doc = new document($this->logger, $this->container, $this->connection, $param);
                    $response[$document['id']] = $doc->ckeckParentDocument();
                }
            }

            return $response;
        }

        // Permet de valider qu'aucun document précédent pour la même règle et le même id sont bloqués
        public function checkParentDocument()
        {
            // Return false if job has been manually stopped
            if (!$this->jobActive) {
                $this->message .= 'Job is not active. ';

                return false;
            }
            $this->connection->beginTransaction(); // -- BEGIN TRANSACTION
            try {
                // S'il y a au moins une relation sur la règle et si on n'est pas sur une règle groupée
                // alors on contôle les enregistrements parent
                if (
                    !empty($this->ruleRelationships)
                    && !$this->isChild()
                ) {
                    $error = false;
                    // Vérification de chaque relation de la règle
                    foreach ($this->ruleRelationships as $ruleRelationship) {
                        if (empty(trim($this->sourceData[$ruleRelationship['field_name_source']]))) {
                            continue; // S'il n'y a pas de relation, on envoie sans erreur
                        }

                        // If the relationship is a parent type, we don't check parent document here. Data will be controlled and read from the child rule when we will send the parent document. So no target id is required now.
                        if (!empty($ruleRelationship['parent'])) {
                            continue;
                        }

                        // Select previous document in the same rule with the same id and status different than closed
                        $targetId = $this->getTargetId($ruleRelationship, $this->sourceData[$ruleRelationship['field_name_source']]);
                        if (empty($targetId['record_id'])) {
                            // If no target id found, we check if the parent has been filtered, in this case we filter the relate document too
                            $documentSearch = $this->searchRelateDocumentByStatus($ruleRelationship, $this->sourceData[$ruleRelationship['field_name_source']], 'Filter');
                            if (!empty($documentSearch['id'])) {
                                $this->docIdRefError = $documentSearch['id'];
                                $this->typeError = 'W';
                                $this->message .= 'Document filter because the parent document is filter too. Check reference column to open the parent document.';
                                $this->updateStatus('Filter');
                                $this->connection->commit(); // -- COMMIT TRANSACTION

                                return false;
                            }
                            $error = true;
                            break;
                        }
                        // Save document relationship to keep the relate id and display document linked into Myddleware
                        $this->insertDocumentRelationship($ruleRelationship, $targetId['document_id']);
                    }
                    // Si aucun document parent n'est trouvé alors bloque le document
                    if ($error) {
                        // récupération du nom de la règle pour avoir un message plus clair
                        $sqlParams = '	SELECT name FROM Rule WHERE id = :rule_id';
                        $stmt = $this->connection->prepare($sqlParams);
                        $stmt->bindValue(':rule_id', $ruleRelationship['field_id']);
                        $stmt->execute();
                        $ruleResult = $stmt->fetch();
                        $direction = $this->getRelationshipDirection($ruleRelationship);
                        throw new Exception('Failed to retrieve a related document. No data for the field '.$ruleRelationship['field_name_source'].'. There is not record with the ID '.('1' == $direction ? 'source' : 'target').' '.$this->sourceData[$ruleRelationship['field_name_source']].' in the rule '.$ruleResult['name'].'. This document is queued. ');
                    }
                }
                // Get the parent document to save it in the table Document for the child document
                $parentDocumentId = '';

                if (!empty($targetId['document_id'])) {
                    $parentDocumentId = $targetId['document_id'];
                }
                // Check if the status was in relate_KO before we set the status Relate_OK
                // In this cas, new data has been created in Myddleware. So we check again if the mode of the document is still Create
                if (
                    'Relate_KO' == $this->status
                    and 'C' == $this->documentType
                ) {
                    $this->documentType = $this->checkRecordExist($this->sourceId);
                    if ('U' == $this->documentType) {
                        $this->updateTargetId($this->targetId);
                        $this->updateType('U');
                    }
                }

                $this->updateStatus('Relate_OK');

                $this->connection->commit(); // -- COMMIT TRANSACTION

                return true;
            } catch (Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                $this->message .= 'No data for the field '.$ruleRelationship['field_name_source'].' in the rule '.$this->ruleName.'. Failed to check document related : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->updateStatus('Relate_KO');
                $this->logger->error($this->message);

                return false;
            }
        }

        // Permet de contrôler si un docuement de la même règle pour le même enregistrement n'est pas close
        // Si un document n'est pas clos alors le statut du docuement est mis à "pending"
        public function transformDocuments($documents = null)
        {
            include_once 'document.php';

            // Permet de charger dans la classe toutes les relations de la règle
            $response = [];

            // Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
            if (empty($documents)) {
                $documents = $rule->getDocumentsByStatus('Relate_OK');
            }
            if (!empty($documents)) {
                $param['ruleFields'] = $this->ruleFields;
                $param['ruleRelationships'] = $this->ruleRelationships;
                $param['jobId'] = $this->jobId;
                $param['api'] = $this->api;
                $param['key'] = $this->key;
                // If migration mode, we select all documents to improve performance. For example, we won't execute queries is method document->getTargetId
                $migrationParameters = $this->params->get('migration');
                if (!empty($migrationParameters['mode'])) {
                    if (!empty($this->ruleRelationships)) {
                        // Get all documents of every rules linked
                        foreach ($this->ruleRelationships as $ruleRelationship) {
                            // Get documents only if we don't have them yet (we could have several relationship to the same rule)
                            if (empty($param['ruleDocuments'][$ruleRelationship['field_id']])) {
                                $param['ruleDocuments'][$ruleRelationship['field_id']] = $this->getRuleDocuments($ruleRelationship['field_id'], true);
                            }
                        }
                    }
                }
                // Transformation de tous les docuements sélectionnés
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    $doc = new document($this->logger, $this->container, $this->connection, $param);
                    $response[$document['id']] = $doc->transformDocument();
                }
            }

            return $response;
        }

        // Permet de transformer les données source en données cibles
        public function transformDocument()
        {
            // Return false if job has been manually stopped
            if (!$this->jobActive) {
                $this->message .= 'Job is not active. ';

                return false;
            }
            $this->connection->beginTransaction(); // -- BEGIN TRANSACTION
            try {
                // Transformation des données et insertion dans la table target
                $transformed = $this->updateTargetTable();
                if ($transformed) {
                    // If the type of this document is Update and the id of the target is missing, we try to get this ID
                    // Except if the rule is a child (no target id is required, it will be send with the parent rule)
                    if (
                        'U' == $this->documentType
                        && empty($this->targetId)
                        && !$this->isChild()
                    ) {
                        $this->checkRecordExist($this->document_data['source_id']);
                        if (!empty($this->targetId)) {
                            if (!$this->updateTargetId($this->targetId)) {
                                throw new Exception('The type of this document is Update. Failed to update the target id '.$this->targetId.' on this document. This document is queued. ');
                            }
                        } else {
                            throw new Exception('The type of this document is Update. The id of the target is missing. This document is queued. ');
                        }
                    }
                } else {
                    throw new Exception('Failed to transformed data. This document is queued. ');
                }
                $this->updateStatus('Transformed');
                $this->connection->commit(); // -- COMMIT TRANSACTION

                return true;
            } catch (Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                $this->message .= 'Failed to transform document : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->updateStatus('Error_transformed');
                $this->logger->error($this->message);

                return false;
            }
        }

        // Permet de récupérer les données de la cible avant modification des données
        // 2 cas de figure :
        //     - Le document est un document de modification
        //     - Le document est un document de création mais la règle a un paramètre de vérification des données pour ne pas créer de doublon
        public function getTargetDataDocuments($documents = null)
        {
            // Permet de charger dans la classe toutes les relations de la règle
            $response = [];

            // Sélection de tous les docuements de la règle au statut 'New' si aucun document n'est en paramètre
            if (empty($documents)) {
                $documents = $rule->getDocumentsByStatus('Transformed');
            }

            if (!empty($documents)) {
                // Connexion à la solution cible pour rechercher les données
                $this->connexionSolution($rule, 'target');

                // Récupération de toutes les données dans la cible pour chaque document
                foreach ($documents as $document) {
                    $param['id_doc_myddleware'] = $document['id'];
                    $param['solutionTarget'] = $this->solutionTarget;
                    $param['ruleFields'] = $this->ruleFields;
                    $param['ruleRelationships'] = $this->ruleRelationships;
                    $param['jobId'] = $this->jobId;
                    $param['api'] = $this->api;
                    $param['key'] = $this->key;
                    $doc = new document($this->logger, $this->container, $this->connection, $param);
                    $response[$document['id']] = $doc->getTargetDataDocument();
                    $response['doc_status'] = $doc->getStatus();
                }
            }

            return $response;
        }

        // Permet de transformer les données source en données cibles
        public function getTargetDataDocument()
        {
            // Return false if job has been manually stopped
            if (!$this->jobActive) {
                $this->message .= 'Job is not active. ';

                return false;
            }
            $history = false;
            $this->connection->beginTransaction(); // -- BEGIN TRANSACTION
            try {
                // Check if the rule is a parent and run the child data.
                $this->runChildRule();

                // If the document type is a modification or a deletion we get target data for the record using its ID
                // And if the rule is not a child (no target id is required, it will be send with the parent rule)
                if (
                    (
                        'U' == $this->documentType
                        or 'D' == $this->documentType
                    )
                    && !$this->isChild()
                ) {
                    // Récupération des données avec l'id de la cible
                    $searchFields = ['id' => $this->targetId];
                    $history = $this->getDocumentHistory($searchFields);

                    // History is mandatory before a delete action, however if no record found, it means that the record has already been deleted
                    if (
                        'D' == $this->documentType
                        and false === $history
                    ) {
                        $this->message .= 'This document type is D (delete) and no record have been found in the target application. It means that the record has already been deleted in the target application. This document is cancelled.';
                        $this->updateStatus('Cancel');
                        $this->connection->commit(); // -- COMMIT TRANSACTION

                        return false;
                    }

                    // From here, the history table has to be filled
                    if (-1 !== $history) {
                        $this->updateStatus('Ready_to_send');
                    } else {
                        throw new Exception('Failed to retrieve record in target system before update or deletion. Id target : '.$this->targetId.'. Check this record is not deleted.');
                    }
                } // Else if create or search document, if we have duplicate_fields, we search the data in target application
                elseif (!empty($this->ruleParams['duplicate_fields'])) {
                    $duplicate_fields = explode(';', $this->ruleParams['duplicate_fields']);
                    // Get the field value from the document target data
                    $target = $this->getDocumentData('T');
                    if (empty($target)) {
                        throw new Exception('Failed to search duplicate data in the target system because there is no target data in this data transfer. This document is queued. ');
                    }
                    // Prepare the search array with teh value for each duplicate field
                    foreach ($duplicate_fields as $duplicate_field) {
                        $searchFields[$duplicate_field] = $target[$duplicate_field];
                    }
                    if (!empty($searchFields)) {
                        $history = $this->getDocumentHistory($searchFields);
                    }

                    if (-1 === $history) {
                        throw new Exception('Failed to search duplicate data in the target system. This document is queued. ');
                    } // Si la fonction renvoie false (pas de données trouvée dans la cible) ou true (données trouvée et correctement mise à jour)
                    elseif (false === $history) {
                        // If search document and don't found the record, we return an error
                        if ('S' == $this->documentType) {
                            $this->typeError = 'E';
                            $this->updateStatus('Not_found');
                        } else {
                            $this->updateStatus('Ready_to_send');
                        }
                    } // renvoie l'id : Si une donnée est trouvée dans le système cible alors on modifie le document pour ajouter l'id target et modifier le type
                    else {
                        // Add message detail when we have found a record
                        if (!empty($searchFields)) {
                            $this->message .= 'Found ';
                            foreach ($searchFields as $key => $value) {
                                $this->message .= $key.' = '.$value.' ; ';
                            }
                        }
                        // If search document we close it.
                        if ('S' == $this->documentType) {
                            $this->updateStatus('Found');
                        } else {
                            $this->updateStatus('Ready_to_send');
                            $this->updateType('U');
                        }
                        $this->updateTargetId($history);
                    }
                } // Sinon on mets directement le document en ready to send (example child rule)
                else {
                    $this->updateStatus('Ready_to_send');
                }
                // S'il n'y a aucun changement entre la cible actuelle et les données qui seront envoyée alors on clos directement le document
                // Si le document est en type recherche, alors la cible est forcément égale à la source et il ne fait pas annuler le doc.
                // We always send data if the rule is parent (the child data could be different even if the parent data didn't change)
                // No check for deletion document
                if (
                    'S' != $this->documentType
                    and 'D' != $this->documentType
                    and !$rule->isParent()
                ) {
                    $this->checkNoChange();
                }
                $this->connection->commit(); // -- COMMIT TRANSACTION
            } catch (Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                $this->message .= $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                if ('S' == $this->documentType) {
                    $this->updateStatus('Not_found');
                } else {
                    $this->updateStatus('Error_checking');
                }
                $this->logger->error($this->message);

                return false;
            }

            return true;
        }

        public function sendDocuments()
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

        // Get the child rule of the current rule
        // If child rule exist, we run it
        protected function runChildRule()
        {
            $ruleParam['ruleId'] = $this->ruleId;
            $ruleParam['jobId'] = $this->jobId;
            $parentRule = new rule($this->logger, $this->container, $this->connection, $ruleParam);
            // Get the child rules of the current rule
            $childRuleIds = $parentRule->getChildRules();
            if (!empty($childRuleIds)) {
                foreach ($childRuleIds as $childRuleId) {
                    // Instantiate the child rule
                    $ruleParam['ruleId'] = $childRuleId['field_id'];
                    $ruleParam['jobId'] = $this->jobId;
                    $childRule = new rule($this->logger, $this->container, $this->connection, $ruleParam);

                    // Build the query in function generateDocuments
                    if (!empty($this->sourceData[$childRuleId['field_name_source']])) {
                        $idQuery = $this->sourceData[$childRuleId['field_name_source']];
                    } else {
                        //throw new \Exception( 'Failed to get the data in the document for the field '.$childRuleId['field_name_source'].'. The query to search to generate child data can\'t be created');
                        continue;
                    }

                    // Generate documents for the child rule (could be several documents) => We search the value of the field_name_source in the field_name_target of the target rule
                    $docsChildRule = $childRule->generateDocuments($idQuery, true, ['parent_id' => $this->id], $childRuleId['field_name_target']);
                    if (!empty($docsChildRule->error)) {
                        throw new Exception($docsChildRule->error);
                    }
                    // Run documents
                    if (!empty($docsChildRule)) {
                        foreach ($docsChildRule as $doc) {
                            $errors = $childRule->actionDocument($doc->id, 'rerun');
                            // If a child is in error, we stop the whole processus : child document not saved (roolback) and parent document in error checking
                            if (!empty($errors)) {
                                // The error should be clear because the child document won't be saved
                                throw new Exception('Child document in error (rule '.$childRuleId['field_id'].')  : '.$errors[0].' The child document has not be saved. Check the log (app/logs/'.$this->container->get('kernel')->getEnvironment().'.log) for more information. ');
                            }
                        }
                    }
                }
            }

            return true;
        }

        // Vérifie si les données sont différente entre ce qu'il y a dans la cible et ce qui devrait être envoyé
        protected function checkNoChange()
        {
            try {
                // Get target data
                $target = $this->getDocumentData('T');

                // Get data in the target solution (if exists) before we update it
                $history = $this->getDocumentData('H');

                // For each target fields, we compare the data we want to send and the data already in the target solution
                // If one is different we stop the function
                if (!empty($this->ruleFields)) {
                    foreach ($this->ruleFields as $field) {
                        if (trim($history[$field['target_field_name']]) != trim($target[$field['target_field_name']])) {
                            return false;
                        }
                    }
                }

                // We check relationship fields as well
                if (!empty($this->ruleRelationships)) {
                    foreach ($this->ruleRelationships as $ruleRelationship) {
                        if ($history[$ruleRelationship['field_name_target']] != $target[$ruleRelationship['field_name_target']]) {
                            return false;
                        }
                    }
                }
                // If all fields are equal, no need to update, so we cancel the document
                $this->message .= 'Identical data to the target system. This document is canceled. ';
                $this->typeError = 'W';
                $this->updateStatus('No_send');

                return true;
            } catch (Exception $e) {
                // If something wrong happen (e.g. a field isn't set) the we return false
                return false;
            }
        }

        // Récupération des données dans la cible et sauvegarde dans la table d'historique
        protected function getDocumentHistory($searchFields)
        {
            // Permet de renseigner le tableau rule avec les données d'entête
            $rule = $this->getRule();
            $read['module'] = $rule['module_target'];
            // Get all fields for document type D (delete) to backup the whole record before delete it
            ('D' == $this->documentType ? $all = true : $all = false);
            $response = $this->getTargetFields($rule, $all);
            $read['fields'] = $response['fields'];
            $read['query'] = $searchFields;
            $read['ruleParams'] = $this->ruleParams;
            $read['rule'] = $rule;
            // In case we search a specific record, we set an default value in date_ref because it is a requiered parameter in the read function
            $read['date_ref'] = '1970-01-02 00:00:00';
            $dataTarget = $this->solutionTarget->read_last($read);
            if (empty($dataTarget['done'])) {
                return false;
            } elseif (-1 === $dataTarget['done']) {
                $this->message .= $dataTarget['error'];

                return -1;
            }
            $updateHistory = $this->updateHistoryTable($dataTarget['values']);
            if (true === $updateHistory) {
                return $dataTarget['values']['id'];
            } // Erreur dans la mise à jour de la table historique

            $this->message .= $dataTarget['error'];

            return -1;
        }

        // Permet de charger les données du système source pour ce document
        protected function getDocumentData($type)
        {
            try {
                $documentDataEntity = $this->entityManager
                    ->getRepository(DocumentData::class)
                    ->findOneBy([
                        'doc_id' => $this->id,
                        'type' => $type,
                    ]
                    );
                // Generate data array
                if (!empty($documentDataEntity)) {
                    return json_decode($documentDataEntity->getData(), true);
                }
            } catch (Exception $e) {
                $this->message .= 'Error getSourceData  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
            }

            return false;
        }

        // Insert source data in table documentData
        protected function insertDataTable(Document $document, $data, $type)
        {
            try {
                $rule = $document->getRule();
                // We retrieve all the target fields (not just the rule flieds) before deleting data to the target solution to create a backup
                if ('D' == $document->getType() && 'H' == $type) {
                    // Get all module fields
                    $response = $this->getTargetFields($rule, true);
                    $targetFields = $response['fields'];
                    // Format these target fields
                    if (!empty($targetFields)) {
                        foreach ($targetFields as $targetField) {
                            $fields[] = ['target_field_name' => $targetField];
                        }
                    }
                } else {
                    $fields = $rule->getFieldsArray();
                }

                // We save only fields which belong to the rule
                if (!empty($fields)) {
                    foreach ($fields as $ruleField) {
                        if ('S' == $type) {
                            // We don't create entry in the array dataInsert when the filed is my_value because there is no filed in the source, just a formula to the target application
                            if ('my_value' == $ruleField['source_field_name']) {
                                continue;
                            }
                            // It could be several fields in the source fields (in case of formula)
                            $sourceFields = explode(';', $ruleField['source_field_name']);
                            foreach ($sourceFields as $sourceField) {
                                // if Myddleware_element_id is present, we transform it into id
                                if ('Myddleware_element_id' == $sourceField) {
                                    $sourceField = 'id';
                                }
                                $dataInsert[$sourceField] = $data[$sourceField];
                            }
                        } else {
                            // Some field can't be retrived from the target application (history). For example the field password on the module user of Moodle
                            if (
                                !array_key_exists($ruleField['target_field_name'], $data)
                                and 'H' == $type
                            ) {
                                continue;
                            }
                            $dataInsert[$ruleField['target_field_name']] = $data[$ruleField['target_field_name']];
                        }
                    }
                }
                // We save the relationship field too
                if ($rule->getRelationsShip()->count() > 0) {
                    foreach ($rule->getRelationsShip() as $ruleRelationship) {
                        // if field = Myddleware_element_id then we take the id record in the osurce application
                        if ('S' == $type) {
                            $dataInsert[$ruleRelationship->getFieldNameSource()] = ('Myddleware_element_id' == $ruleRelationship->getFieldNameSource() ? $data['id'] : ($data[$ruleRelationship->getFieldNameSource()] ?? ''));
                        } else {
                            $dataInsert[$ruleRelationship->getFieldNameTarget()] = $data[$ruleRelationship->getFieldNameTarget()] ?? '';
                        }
                    }
                }
                $documentData = new DocumentDataEntity();
                $documentData->setDocId($document);
                $documentData->setType($type); // Source
                $documentData->setData(json_encode($dataInsert)); // Encode in JSON
                $this->entityManager->persist($documentData);
                $this->entityManager->flush();

                if (empty($documentData->getId())) {
                    return ['success' => false, 'message' => 'Failed to insert data source in table Document Data.'];
                }
            } catch (Exception $e) {
                $message = 'Failed : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($message);

                return ['success' => false, 'message' => $message];
            }

            return ['success' => true, 'document' => $document];
        }

        // Mise à jour de la table des données source
        protected function updateHistoryTable($dataTarget)
        {
            if (!empty($dataTarget)) {
                try {
                    if (!$this->insertDataTable($document, $dataTarget, 'H')) {
                        throw new Exception('Failed insert target data in the table DocumentData.');
                    }

                    return true;
                } catch (Exception $e) {
                    $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $this->typeError = 'E';
                    $this->logger->error($this->message);
                }
            }

            return false;
        }

        // Mise à jour de la table des données cibles
        protected function updateTargetTable()
        {
            try {
                // Loop on every target field and calculate the value
                if (!empty($this->ruleFields)) {
                    foreach ($this->ruleFields as $ruleField) {
                        $value = $this->getTransformValue($this->sourceData, $ruleField);
                        if (!empty($this->transformError)) {
                            throw new Exception('Failed to transform data.');
                        }
                        $targetField[$ruleField['target_field_name']] = $value;
                    }
                }
                // Loop on every relationship and calculate the value
                if (isset($this->ruleRelationships)) {
                    // Récupération de l'ID target
                    foreach ($this->ruleRelationships as $ruleRelationships) {
                        $value = $this->getTransformValue($this->sourceData, $ruleRelationships);
                        if (!empty($this->transformError)) {
                            throw new Exception('Failed to transform relationship data.');
                        }
                        $targetField[$ruleRelationships['field_name_target']] = $value;
                    }
                }
                if (!empty($targetField)) {
                    if (!$this->insertDataTable($document, $targetField, 'T')) {
                        throw new Exception('Failed insert target data in the table DocumentData.');
                    }
                } else {
                    throw new Exception('No target data found. Failed to create target data. ');
                }

                return true;
            } catch (Exception $e) {
                $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
            }

            return false;
        }

        /*
        Fonction permettant de renvoyer la valeur du champ cible en fonction des données sources et de la règle (relation, formule)
        En entrée, 2 tableaux sont attendus
            => Les données source $source, par exemple :
            Array
            (
                [id_test_account_001_source] => 5352886318db0  	: champ non nécessaire en mode test sur le front office
                [name] => test sfaure 003
                [email1] => 003@test.test
            )
            => La définition du champ cible (avec formule et relation) $RuleField, par exemple :
            Array
            (
                [id] => 52 								: champ non nécessaire en mode test sur le front office
                [rule_id] => 53500e0bf2d06 						: champ non nécessaire en mode test sur le front office
                [target_field_name] => name
                [source_field_name] => name
                [formula] =>
                [related_rule] =>
            )
        En sortie la fonction renvoie la valeur du champ à envoyer dans le cible
         */
        public function getTransformValue($source, $ruleField)
        {
            try {
                //--
                if (!empty($ruleField['formula'])) {
                    // -- -- -- Formula management

                    // Build variables
                    $r = explode(';', $ruleField['source_field_name']);
                    if (count($r) > 1) {
                        foreach ($r as $listFields) {
                            // We skip my_value because it is a constante
                            if ('my_value' != $listFields) {
                                $fieldNameDyn = $listFields; // value : variable name
                                if (array_key_exists($listFields, $source)) {
                                    $$fieldNameDyn = (!empty($source[$listFields]) ? $source[$listFields] : ''); // Dynamic variable (e.g $name = name)
                                } else {
                                    // Erreur
                                    throw new Exception('The field '.$listFields.' is unknow in the formula '.$ruleField['formula'].'. ');
                                }
                            }
                        }
                    } else {
                        // We skip my_value because it is a constante
                        if ('my_value' != $ruleField['source_field_name']) {
                            $fieldNameDyn = $ruleField['source_field_name']; // value : variable name
                            $$fieldNameDyn = $source[$ruleField['source_field_name']]; // Dynamic variable (e.g $name = name)
                        }
                    }
                    // préparation des variables

                    $this->formule->init($ruleField['formula']); // mise en place de la règle dans la classe
                    $this->formule->generateFormule(); // Genère la nouvelle formule à la forme PhP

                    // Exécute la règle si pas d'erreur de syntaxe
                    if (
                    $f = $this->formule->execFormule()
                    ) {
                        // Try the formula first
                        try {
                            eval($f.';'); // exec
                        } catch (\ParseError $e) {
                            throw new Exception('FATAL error because of Invalid formula "'.$ruleField['formula'].';" : '.$e->getMessage());
                        }
                        // Execute eval only if formula is valid
                        eval('$rFormula = '.$f.';'); // exec
                        if (isset($rFormula)) {
                            // affectation du résultat
                            return $rFormula;
                        }
                        throw new Exception('Invalid formula (failed to retrieve formula) : '.$ruleField['formula']);
                    } else {
                        throw new Exception('Invalid formula (failed to execute) : '.$ruleField['formula']);
                    }
                    // -- -- -- Gestion des formules
                } // S'il s'agit d'un champ relation
                elseif (!empty($ruleField['field_id'])) {
                    // Si l'id est vide on renvoie vide
                    if (empty(trim($source[$ruleField['field_name_source']]))) {
                        return;
                    }

                    // If the relationship is a parent type, we don't search the id in the child rule now. Data will be read from the child rule when we will send the parent document. So no target id is required now.
                    if (!empty($ruleField['parent'])) {
                        return;
                    }

                    // Récupération de l'ID de l'enregistrement lié dans la cible avec l'id correspondant dans la source et la correspondance existante dans la règle liée.
                    $targetId = $this->getTargetId($ruleField, $source[$ruleField['field_name_source']]);
                    if (!empty($targetId['record_id'])) {
                        return $targetId['record_id'];
                    }
                    throw new Exception('Target id not found for id source '.$source[$ruleField['field_name_source']].' of the rule '.$ruleField['field_id']);
                } // Si le champ est envoyé sans transformation
                elseif (isset($source[$ruleField['source_field_name']])) {
                    return $this->checkField($source[$ruleField['source_field_name']]);
                } // If Myddleware_element_id is requested, we return the id
                elseif (
                    'Myddleware_element_id' == $ruleField['source_field_name']
                    and isset($source['id'])
                ) {
                    return $this->checkField($source['id']);
                } elseif (is_null($source[$ruleField['source_field_name']])) {
                    return;
                } else {
                    throw new Exception('Field '.$ruleField['source_field_name'].' not found in source data.------'.print_r($ruleField, true));
                }
            } catch (Exception $e) {
                $this->typeError = 'E';
                $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($this->message);
                // Set the error to true. We can't set a specific value in the return because this function could return any value (even false depending the formula)
                $this->transformError = true;

                return;
            }
        }

        // Fonction permettant de contrôle les données.
        protected function checkField($value)
        {
            if (isset($value)) {
                return $value;
            }
        }

        // Permet de récupérer les données d'entête de la règle
        protected function getRule()
        {
            try {
                if (!empty($this->ruleId)) {
                    $rule = 'SELECT * FROM Rule WHERE id = :ruleId';
                    $stmt = $this->connection->prepare($rule);
                    $stmt->bindValue(':ruleId', $this->ruleId);
                    $stmt->execute();

                    return $stmt->fetch();
                }
            } catch (Exception $e) {
                $this->typeError = 'E';
                $this->message .= 'Error getRule  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($this->message);
            }
        }

        // Check if the document is a child
        public function isChild()
        {
            $sqlIsChild = '	SELECT Rule.id 
									FROM RuleRelationShip 
										INNER JOIN Rule
											ON Rule.id  = RuleRelationShip.rule_id 
									WHERE 
											RuleRelationShip.field_id = :ruleId
										AND RuleRelationShip.parent = 1
										AND Rule.deleted = 0
								';
            $stmt = $this->connection->prepare($sqlIsChild);
            $stmt->bindValue(':ruleId', $this->ruleId);
            $stmt->execute();
            $isChild = $stmt->fetch(); // 1 row
            if (!empty($isChild)) {
                return true;
            }

            return false;
        }

        // Check if the document is a child
        protected function getChildDocuments()
        {
            try {
                $sqlGetChilds = 'SELECT * FROM Document WHERE parent_id = :docId AND deleted = 0 ';
                $stmt = $this->connection->prepare($sqlGetChilds);
                $stmt->bindValue(':docId', $this->id);
                $stmt->execute();

                return $stmt->fetchAll();
            } catch (Exception $e) {
                $this->typeError = 'E';
                $this->message .= 'Error getTargetFields  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($this->message);
            }
        }

        // Permet de récupérer les champs de la cible
        protected function getTargetFields(Rule $rule = null, $all = false)
        {
            $fields = [];
            try {
                // if all fields are requested
                if ($all) {
                    $targetFields = $this->solutionTarget->get_module_fields($rule->getModuleTarget());
                    if (!empty($targetFields)) {
                        foreach ($targetFields as $fieldname => $value) {
                            $fields[] = $fieldname;
                        }
                    }
                } elseif (null !== $rule) {
                    $ruleFields = $rule->getFields();
                    foreach ($ruleFields as $ruleField) {
                        $fields[] = $ruleField->getTarget();
                    }

                    // Ajout des champs de relation s'il y en a
                    $ruleRelationShips = $rule->getRelationsShip();
                    if (!empty($ruleRelationShips)) {
                        foreach ($ruleRelationShips as $ruleRelationShip) {
                            // If it is a normal relationship we take the target field
                            // but if it is a parent relationship we have to take the source field in the relation (wich corresponding to the target field)
                            if (!$ruleRelationShip->getParent()) {
                                $fields[] = $ruleRelationShip->getFieldNameTarget();
                            } else {
                                $fields[] = $ruleRelationShip->getFieldNameSource();
                            }
                        }
                    }
                }
                // We don't need the field Myddleware_element_id as it is the id of the current record
                if (!empty($fields)) {
                    $key = array_search('Myddleware_element_id', $fields);
                    if (false !== $key) {
                        unset($fields[$key]);
                    }
                }

                return ['success' => true, 'fields' => $fields];
            } catch (Exception $e) {
                $message = 'Error getTargetFields  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($message);

                return [
                    'success' => false,
                    'message' => $message,
                    'typeError' => 'E',
                    'fields' => [],
                ];
            }
        }

        // Permet de charger tous les paramètres de la règle
        protected function setRuleParam()
        {
            try {
                $sqlParams = 'SELECT * 
							FROM RuleParam 
							WHERE rule_id = :ruleId';
                $stmt = $this->connection->prepare($sqlParams);
                $stmt->bindValue(':ruleId', $this->ruleId);
                $stmt->execute();
                $ruleParams = $stmt->fetchAll();
                if ($ruleParams) {
                    foreach ($ruleParams as $ruleParam) {
                        $this->ruleParams[$ruleParam['name']] = ltrim($ruleParam['value']);
                    }
                }
            } catch (Exception $e) {
                $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            }
        }

        // Permet de déterminer le type de document (Create ou Update)
        // En entrée : l'id de l'enregistrement source
        // En sortie : le type de docuement (C ou U)
        protected function checkRecordExist(Rule $rule, Document $document, int $id)
        {
            try {
                // Query used in the method several times
                // Sort : target_id to get the target id non empty first; on global_status to get Cancel last
                // We dont take cancel document excpet if it is a no_send document (data really exists in this case)

                //todo: user $this->>documentRepository->findDocumentBySourceOrTarget()
                $sqlParamsSoure = "	SELECT 
								Document.id, 
								Document.target_id, 
								Document.global_status 
							FROM Document 
							WHERE 
									Document.rule_id IN (:ruleId)	
								AND (
										Document.global_status = 'Close'
									 OR (
											Document.global_status = 'Cancel'	
										AND Document.status = 'No_send'
									)
								)
								AND	Document.source_id = :id
								AND Document.id != :id_doc
								AND Document.deleted = 0 
							ORDER BY target_id DESC, global_status DESC
							LIMIT 1";

                // On prépare la requête pour rechercher dans la partie target
                $sqlParamsTarget = "SELECT 
								Document.id, 
								Document.source_id target_id, 
								Document.global_status 
							FROM Document 
							WHERE 
									Document.rule_id IN (:ruleId)	
								AND (
										Document.global_status = 'Close'
									 OR (
											Document.global_status = 'Cancel'	
										AND Document.status = 'No_send'
									)
								)
								AND	Document.target_id = :id
								AND Document.id != :id_doc
								AND Document.deleted = 0 
							ORDER BY target_id DESC, global_status DESC
							LIMIT 1";

                // Si une relation avec le champ Myddleware_element_id est présente alors on passe en update et on change l'id source en prenant l'id de la relation
                // En effet ce champ indique que l'on va modifié un enregistrement créé par une autre règle
                if ($rule->getRelationsShip()->count()) {
                    // Boucle sur les relation
                    foreach ($rule->getRelationsShip() as $ruleRelationship) {
                        // If the relationship target is Myddleware element id and if the rule relate isn't a child (we don't get target id or define type of a document with a child rule)
                        if ('Myddleware_element_id' == $ruleRelationship->getFieldNameTarget() && $ruleRelationship->getParent()) {
                            // Si le champs avec l'id source n'est pas vide
                            // S'il s'agit de Myddleware_element_id on teste id
                            if (
                                !empty($this->data[$ruleRelationship->getFieldNameTarget()])
                                || (
                                    'Myddleware_element_id' == $ruleRelationship['field_name_source']
                                    && !empty($this->data['id'])
                                )
                            ) {
                                // On recherche l'id target dans la règle liée
                                $this->sourceId = ('Myddleware_element_id' == $ruleRelationship['field_name_source'] ? $this->data['id'] : $this->data[$ruleRelationship['field_name_source']]);
                                // On récupère la direction de la relation pour rechercher dans le target id ou dans le source id
                                $direction = $this->getRelationshipDirection($ruleRelationship);
                                if ('-1' == $direction) {
                                    $stmt = $this->connection->prepare($sqlParamsTarget);
                                } else {
                                    $stmt = $this->connection->prepare($sqlParamsSoure);
                                }
                                $stmt->bindValue(':ruleId', $ruleRelationship['field_id']);
                                $stmt->bindValue(':id', $this->sourceId);
                                $stmt->bindValue(':id_doc', $this->id);
                                $stmt->execute();
                                $result = $stmt->fetch();

                                // Si on trouve la target dans la règle liée alors on passe le doc en UPDATE (the target id can be found even if the relationship is a parent (if we update data), but it isn't required)
                                if (!empty($result['target_id'])) {
                                    $this->targetId = $result['target_id'];

                                    return 'U';
                                }
                                // Sinon on bloque la création du document
                                // Except if the rule is parent, no need of target_id, the target id will be retrived when we will send the data
                                elseif (empty($ruleRelationship['parent'])) {
                                    $this->message .= 'Failed to get the id target of the current module in the rule linked.';
                                }
                                // If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
                                if (
                                    empty($result['id'])
                                    || 'Cancel' == $result['global_status']
                                ) {
                                    return 'C';
                                }

                                return 'U';
                            }
                            throw new Exception('The field '.$ruleRelationship['field_name_source'].' used in the relationship is empty. Failed to create the document.');
                        }
                    }
                }
                // A mass process exist for migration mode
                if (!empty($this->ruleDocuments[$this->ruleId])) {
                    // If a least one record is already existing, we test if it was successfully sent
                    if (!empty($this->ruleDocuments[$this->ruleId]['sourceId'][$id])) {
                        foreach ($this->ruleDocuments[$this->ruleId]['sourceId'][$id] as $document) {
                            if (
                                (
                                    'Cancel' != $document['global_status']
                                    or (
                                        'Cancel' == $document['global_status']
                                        and 'No_send' == $document['status']
                                    )
                                )
                                and $document['id'] !== $this->id
                            ) {
                                // Si on trouve la target dans la règle liée alors on passe le doc en UPDATE (the target id can be found even if the relationship is a parent (if we update data), but it isn't required)
                                if (!empty($document['target_id'])) {
                                    $this->targetId = $document['target_id'];

                                    return 'U';
                                }
                                // If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
                                if (
                                    empty($result['id'])
                                    || 'Cancel' == $result['global_status']
                                ) {
                                    return 'C';
                                }

                                return 'U';
                            }
                        }
                    }
                } else {
                    // If no relationship or no child rule
                    // Recherche d'un enregitsrement avec un target id sur la même source
                    $stmt = $this->connection->prepare($sqlParamsSoure);
                    $stmt->bindValue(':ruleId', $this->ruleId);
                    $stmt->bindValue(':id', $id);
                    $stmt->bindValue(':id_doc', $this->id);
                    $stmt->execute();
                    $result = $stmt->fetch();
                }

                // Si on n'a pas trouvé de résultat et que la règle à une équivalente inverse (règle bidirectionnelle)
                // Alors on recherche dans la règle opposée
                if (
                    empty($result['id'])
                    && !empty($this->ruleParams['bidirectional'])
                ) {
                    $stmt = $this->connection->prepare($sqlParamsTarget);
                    $stmt->bindValue(':ruleId', $this->ruleParams['bidirectional']);
                    $stmt->bindValue(':id', $id);
                    $stmt->bindValue(':id_doc', $this->id);
                    $stmt->execute();
                    $result = $stmt->fetch();
                }

                // If we found a record
                if (!empty($result['id'])) {
                    $this->targetId = $result['target_id'];
                    // If the document found is Cancel, there is only Cancel documents (see query order) so we return C and not U
                    // Except if the rule is bidirectional, in this case, a no send document in the opposite rule means that the data really exists in the target application
                    if (
                        'Cancel' == $result['global_status']
                        && empty($this->ruleParams['bidirectional'])
                    ) {
                        return 'C';
                    }

                    return 'U';
                }
                // Si on est sur une règle child alors on est focément en update (seule la règle root est autorisée à créer des données)
                // We check now because we take every chance we can to get the target_id
                if ($this->isChild()) {
                    return 'U';
                }
                // Si aucune règle avec relation Myddleware_element_id alors on est en création
                return 'C';
            } catch (Exception $e) {
                $this->typeError = 'E';
                $this->message .= 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($this->message);

                return;
            }
        }

        public function documentCancel()
        {
            // Search if the document has child documents
            $childDocuments = $this->getChildDocuments();
            if (!empty($childDocuments)) {
                // We cancel each child, but a child document can be a parent document too, so we make a recursive call
                foreach ($childDocuments as $childDocument) {
                    // We don't Cancel a document if it has been already cancelled
                    if ('Cancel' != $childDocument['global_status']) {
                        $param['id_doc_myddleware'] = $childDocument['id'];
                        $param['jobId'] = $this->jobId;
                        $docChild = new document($this->logger, $this->container, $this->connection, $param);
                        $docChild->documentCancel();
                    }
                }
            }
            $this->updateStatus('Cancel');
        }

        public function changeDeleteFlag($deleteFlag)
        {
            $this->updateDeleteFlag($deleteFlag);
        }

        /**
         * @param null   $typeError
         * @param string $message
         */
        public function updateStatus(Document $document, string $newStatus, bool $api = false, $typeError = null, $message = '')
        {
            // Récupération du statut global
            $globalStatus = $this->globalStatus[$newStatus];
            // Ajout d'un essai si erreur
            if ('Error' == $globalStatus || 'Close' == $globalStatus) {
                $document->addAttempt();
            }
            $now = new DateTime();
            $document
                ->setGlobalStatus($globalStatus)
                ->setStatus($newStatus)
                ->setDateModified($now);

            if (false === $api) {
                echo 'statut '.$newStatus.' id = '.$document->getId().'  '.$now->format('dmY');
            }
            // Suppression de la dernière virgule
            $message .= 'Status : '.$newStatus;
            $this->entityManager->flush();

            $this->createDocLog($document, $message, $typeError);
        }

        public function updateDeleteFlag($deleted)
        {
            $this->connection->beginTransaction(); // -- BEGIN TRANSACTION
            try {
                $now = gmdate('Y-m-d H:i:s');
                $query = '	UPDATE Document 
								SET 
									date_modified = :now,
									deleted = :deleted
								WHERE
									id = :id
								';
                if (!$this->api) {
                    echo (!empty($deleted) ? 'Remove' : 'Restore').' document id = '.$this->id.'  '.$now.chr(10);
                }
                $stmt = $this->connection->prepare($query);
                $stmt->bindValue(':now', $now);
                $stmt->bindValue(':deleted', $deleted);
                $stmt->bindValue(':id', $this->id);
                $stmt->execute();
                $this->message .= (!empty($deleted) ? 'Remove' : 'Restore').' document';
                $this->connection->commit(); // -- COMMIT TRANSACTION
                $this->createDocLog();
            } catch (Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                $this->message .= 'Failed to '.(!empty($deleted) ? 'Remove ' : 'Restore ').' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
                $this->createDocLog();
            }
        }

        // Save document relationship
        protected function insertDocumentRelationship($ruleRelationship, $docRelId)
        {
            try {
                // Add the relationship in the table document Relationship
                $documentRelationship = new DocumentRelationship();
                $documentRelationship->setDocId($this->id);
                $documentRelationship->setDocRelId($docRelId);
                $documentRelationship->setDateCreated(new DateTime());
                $documentRelationship->setCreatedBy((int) $this->userId);
                $documentRelationship->setSourceField($ruleRelationship['field_name_source']);
                $this->entityManager->persist($documentRelationship);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->message .= 'Failed to save the document relationship for the field '.$ruleRelationship['field_name_source'].' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'W';
                $this->logger->error($this->message);

                return false;
            }
        }

        // Permet d'intervenir avant le changement de statut
        protected function beforeStatusChange($new_status)
        {
            return $new_status;
        }

        // Permet d'intervenir après le changement de statut
        protected function afterStatusChange($new_status)
        {
        }

        // Permet de modifier le type du document
        public function updateType($new_type)
        {
            $this->connection->beginTransaction(); // -- BEGIN TRANSACTION
            try {
                $now = gmdate('Y-m-d H:i:s');
                $query = '	UPDATE Document 
								SET 
									date_modified = :now,
									type = :new_type
								WHERE
									id = :id
								';
                // Suppression de la dernière virgule
                $stmt = $this->connection->prepare($query);
                $stmt->bindValue(':now', $now);
                $stmt->bindValue(':new_type', $new_type);
                $stmt->bindValue(':id', $this->id);
                $stmt->execute();
                $this->message .= 'Type  : '.$new_type;
                $this->connection->commit(); // -- COMMIT TRANSACTION
                $this->createDocLog();
            } catch (Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                $this->message .= 'Error type   : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
                $this->createDocLog();
            }
        }

        // Permet de modifier le type du document
        public function updateTargetId($target_id)
        {
            $this->connection->beginTransaction(); // -- BEGIN TRANSACTION
            try {
                $now = gmdate('Y-m-d H:i:s');
                $query = '	UPDATE Document 
								SET 
									date_modified = :now,
									target_id = :target_id
								WHERE
									id = :id
								';
                // Suppression de la dernière virgule
                $stmt = $this->connection->prepare($query);
                $stmt->bindValue(':now', $now);
                // Target id could contain accent
                $stmt->bindValue(':target_id', utf8_encode($target_id));
                $stmt->bindValue(':id', $this->id);
                $stmt->execute();
                $this->message .= 'Target id : '.$target_id;
                $this->connection->commit(); // -- COMMIT TRANSACTION
                $this->createDocLog();

                return true;
            } catch (Exception $e) {
                $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
                $this->message .= 'Error target id  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
                $this->createDocLog();

                return false;
            }
        }

        protected function getRelationshipDirection($ruleRelationship)
        {
            try {
                // Calcul du sens de la relation. Si on ne trouve pas (exemple des relations custom) alors on met 1 par défaut.
                $sqlParams = "	SELECT 
								IF(RuleA.conn_id_source = RuleB.conn_id_source, '1', IF(RuleA.conn_id_source = RuleB.conn_id_target, '-1', '1')) direction
							FROM RuleRelationShip
								INNER JOIN Rule RuleA
									ON RuleRelationShip.rule_id = RuleA.id
									#AND RuleA.deleted = 0
								INNER JOIN Rule RuleB
									ON RuleRelationShip.field_id = RuleB.id		
									#AND RuleB.deleted = 0
							WHERE  
								RuleRelationShip.id = :id 
						";
                $stmt = $this->connection->prepare($sqlParams);
                $stmt->bindValue(':id', $ruleRelationship['id']);
                $stmt->execute();
                $result = $stmt->fetch();
                if (!empty($result['direction'])) {
                    return $result['direction'];
                }

                return;
            } catch (Exception $e) {
                return;
            }
        }

        // Permet de récupérer l'id target pour une règle et un id source ou l'inverse
        protected function getTargetId($ruleRelationship, $record_id)
        {
            try {
                $direction = $this->getRelationshipDirection($ruleRelationship);
                // En fonction du sens de la relation, la recherche du parent id peut-être inversée (recherchée en source ou en cible)
                // Search all documents with target ID not empty in status close or no_send (document canceled but it is a real document)
                if ('-1' == $direction) {
                    $sqlParams = "	SELECT 
									source_id record_id,
									Document.id document_id								
								FROM Document
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.source_id != '' 
									AND Document.deleted = 0 
									AND Document.target_id = :record_id 
									AND (
											Document.global_status = 'Close' 
										 OR Document.status = 'No_send'
									)	 
								LIMIT 1";
                } elseif ('1' == $direction) {
                    $sqlParams = "	SELECT 
									target_id record_id,
									Document.id document_id
								FROM Document 
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.source_id = :record_id 
									AND Document.deleted = 0 
									AND Document.target_id != '' 
									AND (
											Document.global_status = 'Close' 
										 OR Document.status = 'No_send'
									)	
								LIMIT 1";
                } else {
                    throw new Exception('Failed to find the direction of the relationship with the rule_id '.$ruleRelationship['field_id'].'. ');
                }

                // A mass process exist for migration mode
                if (!empty($this->ruleDocuments[$ruleRelationship['field_id']])) {
                    // We search the target/source id in the array in memory
                    if ('1' == $direction) {
                        if (!empty($this->ruleDocuments[$ruleRelationship['field_id']]['sourceId'][$record_id])) {
                            foreach ($this->ruleDocuments[$ruleRelationship['field_id']]['sourceId'][$record_id] as $document) {
                                if (
                                    (
                                        'Close' == $document['global_status']
                                        or 'No_send' == $document['status']
                                    )
                                    and '' != $document['target_id']
                                ) {
                                    $result['record_id'] = $document['target_id'];
                                    $result['document_id'] = $document['id'];
                                    break;
                                }
                            }
                        }
                    } else {
                        if (!empty($this->ruleDocuments[$ruleRelationship['field_id']]['targetId'][$record_id])) {
                            foreach ($this->ruleDocuments[$ruleRelationship['field_id']]['targetId'][$record_id] as $document) {
                                if (
                                    (
                                        'Close' == $document['global_status']
                                        or 'No_send' == $document['status']
                                    )
                                    and '' != $document['source_id']
                                ) {
                                    $result['record_id'] = $document['source_id'];
                                    $result['document_id'] = $document['id'];
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $stmt = $this->connection->prepare($sqlParams);
                    $stmt->bindValue(':ruleRelateId', $ruleRelationship['field_id']);
                    $stmt->bindValue(':record_id', $record_id);
                    $stmt->execute();
                    $result = $stmt->fetch();
                }
                if (!empty($result['record_id'])) {
                    return $result;
                }

                return;
            } catch (Exception $e) {
                $this->message .= 'Error getTargetId  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->typeError = 'E';
                $this->logger->error($this->message);
            }
        }

        // Search relate document by status
        protected function searchRelateDocumentByStatus($ruleRelationship, $record_id, $status)
        {
            try {
                $direction = $this->getRelationshipDirection($ruleRelationship);
                // En fonction du sens de la relation, la recherche du parent id peut-être inversée (recherchée en source ou en cible)
                // Search all documents with target ID not empty in status close or no_send (document canceled but it is a real document)
                if ('-1' == $direction) {
                    $sqlParams = '	SELECT *								
								FROM Document
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.target_id = :record_id 
									AND Document.status = :status 
									AND Document.deleted = 0 
								LIMIT 1';
                } elseif ('1' == $direction) {
                    $sqlParams = '	SELECT *
								FROM Document 
								WHERE  
										Document.rule_id = :ruleRelateId 
									AND Document.source_id = :record_id 
									AND Document.status = :status 
									AND Document.deleted = 0 
								LIMIT 1';
                } else {
                    throw new Exception('Failed to find the direction of the relationship with the rule_id '.$ruleRelationship['field_id'].'. ');
                }
                $stmt = $this->connection->prepare($sqlParams);
                $stmt->bindValue(':ruleRelateId', $ruleRelationship['field_id']);
                $stmt->bindValue(':record_id', $record_id);
                $stmt->bindValue(':status', $status);
                $stmt->execute();
                $result = $stmt->fetch();
                if (!empty($result['id'])) {
                    return $result;
                }
            } catch (Exception $e) {
                $this->message .= 'Error searchRelateDocumentByStatus  : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($this->message);
            }
        }

        // Permet de renvoyer le statut du document
        public function getStatus()
        {
            return $this->status;
        }

        // Fonction permettant de créer un log pour un docuement
        // Les id de la soluton, de la règle et du document
        // $type peut contenir : I (info;), W(warning), E(erreur), S(succès)
        // $code contient le code de l'erreur
        // $message contient le message de l'erreur avec potentiellement des variable &1, &2...
        // $data contient les varables du message de type array('id_contact', 'nom_contact')
        protected function createDocLog(Document $document, string $message, Job $job, string $typeError = null, $docIdRefError = null)
        {
            $log = new Log();
            $log->setDateCreated(new DateTime())
                ->setRule($document->getRule())
                ->setDocument($document->getId())
                ->setJob($job ? $job->getId() : null)
                ->setRef($docIdRefError)
                ->setType($typeError)
                ->setMessage(str_replace("'", '', utf8_encode($message)))
            ;
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }
}
