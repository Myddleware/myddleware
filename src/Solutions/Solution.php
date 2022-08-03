<?php

declare(strict_types=1);

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

namespace App\Solutions;

use App\Entity\Connector;
use App\Entity\Document;
use App\Entity\Rule;
use App\Manager\DocumentManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Solution
{
    public bool $isConnectionValid = false;

    public int $js = 0;

    public bool $refreshToken = false;

    public bool $callback = false;

    protected $session;

    protected array $moduleFields = [];

    protected array $requiredFields = [];

    protected array $fieldsRelate = [];

    protected array $requiredRelationships = [];

    protected array $connectionParam;

    protected LoggerInterface $logger;

    protected array $validDatabaseTypes = ['text'];

    // Liste des modules à exclure pour chaque solution
    protected array $excludedModules = [
        'default' => [],
        'target' => [],
        'source' => [],
    ];

    protected array $excludedFields = [];

    // Module list that allows to make parent relationships
    protected array $allowParentRelationship = [];

    // Enable the read record button on the data transfer detail view for the source solution
    protected bool $readRecord = true;

    // Disable to read deletion and to delete data
    protected bool $readDeletion = false;

    protected bool $sendDeletion = false;

    // Specify if the class is called by the API
    protected $api;

    protected $message;

    protected Connection $connection;

    protected ParameterBagInterface $parameterBagInterface;

    protected EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        ParameterBagInterface $parameterBagInterface,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->parameterBagInterface = $parameterBagInterface;
    }

    /**
     * Cette méthode doit mettre à jour les attributs :
     * $this->session avec la session de la solution
     * $this->isConnectionValid (true si la connexion est réussie, false sinon).
     */
    public function login(array $connectionParam): void
    {
        // Instanciate object to decrypte data
        $encrypter = new \Illuminate\Encryption\Encrypter(substr($this->parameterBagInterface->get('secret'), -16));
        // Decrypt connexion parameters
        foreach ($connectionParam as $key => $value) {
            if (is_string($value)) {
                try {
                    $connectionParam[$key] = $encrypter->decrypt($value);
                } catch (\Exception $e) {
                    // No error if decrypt failed because some data aren't crypted (eg reference date)
                }
            }
        }
        // Check whether the URL input ends with /, if yes, remove it before making the call
        if (isset($connectionParam['url']) && str_ends_with($connectionParam['url'], '/')) {
            $connectionParam['url'] = substr($connectionParam['url'], 0, -1);
        }

        $this->connectionParam = $connectionParam;
    }

    public function logout(): bool
    {
        return true;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function getConn(): Connection
    {
        return $this->connection;
    }

    /**
     * @throws Exception
     */
    protected function updateDocumentStatus($idDoc, $value, $param, $forceStatus = null): array
    {
        $this->connection->beginTransaction();
        $documentManager = new DocumentManager($this->logger, $this->connection, $this->entityManager);
        try {
            $param['id_doc_myddleware'] = $idDoc;
            $param['api'] = $this->api;
            $documentManager->setParam($param);
            // If a message exist, we add it to the document logs
            if (!empty($value['error'])) {
                $documentManager->setMessage($value['error']);
                $this->message = '';
            }
            // Mise à jour de la table document avec l'id target comme id de document
            // Si la création a fonctionné
            if ('-1' != $value['id']) {
                if (empty($forceStatus)) {
                    $status = 'Send';
                } else {
                    $status = $forceStatus;
                }
                // In cas of a child document, it is possible to have $value['id'] empty,
                // we just set an error because the document can't be sent again (parent document successfully sent)
                if (!empty($value['id'])) {
                    $documentManager->updateTargetId($value['id']);
                } else {
                    $documentManager->setMessage('No target ID found in return of the parent document creation. ');
                }
                $documentManager->updateStatus($status);
                $response[$idDoc] = true;
            } else {
                if (empty($forceStatus)) {
                    $status = 'Error_sending';
                } else {
                    $status = $forceStatus;
                }

                $documentManager->setMessage('Failed to send document. ');
                $documentManager->setTypeError('E');
                $documentManager->updateStatus($status);
                $response[$idDoc] = false;
            }
            $this->connection->commit(); // -- COMMIT TRANSACTION
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            echo 'Failed to send document : '.$errorMessage;
            $this->connection->rollBack(); // -- ROLLBACK TRANSACTION
            $documentManager->setMessage('Failed to send document : '.$errorMessage);
            $documentManager->setTypeError('E');
            $documentManager->updateStatus('Error_sending');
            $this->logger->error('Failed to send document : '.$errorMessage);
            $response[$idDoc] = false;
        }

        return $response;
    }

    /**
     * Cette méthode renvoie un tableau permettant d'indiquer tous les champs nécessaire à la connexion (login, mot de passe...)
     * Exemple de tableau
     * array(
     *   array(
     *     'name' => 'login',
     *     'type' => 'text',
     *     'label' => 'solution.fields.login'
     *   ),
     *   array(
     *     'name' => 'password',
     *     'type' => 'password',
     *     'label' => 'solution.fields.password'
     *   )
     * );.
     */
    public function getFieldsLogin(): array
    {
        return [];
    }

    // Même structure que la méthode getFieldsLogin
    // Prend en paramètre d'entre source ou target
    public function getFieldsParamUpd(string $type, string $module): array
    {
        return [];
    }

    // Renvoie la liste des champs sur lequel on peut vérifier les doublons
    public function getFieldsDuplicate(string $module)
    {
        if (isset($this->fieldsDuplicate[$module])) {
            return $this->fieldsDuplicate[$module];
        } elseif (isset($this->fieldsDuplicate['default'])) {
            return $this->fieldsDuplicate['default'];
        }

        return false;
    }

    /**
     * Méthode permettant de récupérer le nom de tous les modules accessible à l'utilisateur
     * Tableau sous la forme :
     * array(
     *   nom_module1 => libellé module 1,
     *   nom_module2 => libellé module 2
     * ).
     */
    public function getSolutionModules(string $type = 'source'): ?array
    {
        return [];
    }

    public function getModuleFields(string $module, string $type = 'source', $param = null): ?array
    {
        // The field Myddleware_element_id is ID of the current module. It is always added for the field mapping
        $this->moduleFields['Myddleware_element_id'] = [
            'label' => 'ID '.$module,
            'type' => 'varchar(255)',
            'type_bdd' => 'varchar(255)',
            'required' => 0,
            'relate' => true,
        ];

        return $this->moduleFields;
    }

    /**
     * Permet d'ajouter des règles en relation si les règles de gestion standard ne le permettent pas
     * Par exemple si on veut connecter des règles de la solution SAP CRM avec la solution SAP qui sont 2 solutions différentes qui peuvent être connectées.
     */
    public function getRuleCustomRelationship($module, $type, array $parameters = []): ?array
    {
        return null;
    }

    // Helper function for the read call
    public function readData(array $param): ?array
    {
        try {
            $result['count'] = 0;
            if (empty($param['limit'])) {
                $param['limit'] = 100;
            }
            if (empty($param['offset'])) {
                $param['offset'] = 0;
            }
            // Add requiered fields based on attribute $requiredFields
            $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);
            $param['fields'] = array_unique($param['fields']);
            // Remove Myddleware specific fields (not existing in the solution)
            $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);

            // Read data
            $readResult = $this->read($param);

            // Save the new rule params into attribut dataSource
            if (!empty($readResult['ruleParams'])) {
                $result['ruleParams'] = $readResult['ruleParams'];
                unset($readResult['ruleParams']);
            }

            // Format data
            if (!empty($readResult)) {
                // Get the name of the field used for the reference
                $dateRefField = $this->getRefFieldName($param['module'], $param['ruleParams']['mode']);
                // Get the name of the field used as id
                $idField = $this->getIdName($param['module']);

                // Sort data with the reference field
                $modified = array_column($readResult, $dateRefField);
                array_multisort($modified, SORT_ASC, $readResult);

                // Add id and date_modified values into the read call result
                foreach ($readResult as $record) {
                    // If the id column hasn't been defined in the read method we calculate it.
                    if (empty($record['id'])) {
                        if (empty($record[$idField])) {
                            throw new \Exception('Id field '.$idField.' is missing in this record '.print_r($record, true).'.');
                        }
                        $record['id'] = $record[$idField];
                    }
                    // If the date_modified column hasn't been defined in the read method we calculate it.
                    if (empty($record['date_modified'])) {
                        if (empty($record[$dateRefField])) {
                            throw new \Exception('Reference field '.$dateRefField.' is missing in this record '.print_r($record, true).'.');
                        }
                        // Convert date ref into Myddleware format
                        $record['date_modified'] = $this->getModifiedDate($param, $record, $dateRefField);
                    } else {
                        $record['date_modified'] = $this->dateTimeToMyddleware($record['date_modified']);
                    }
                    $result['values'][$record['id']] = $record;
                    // Return the number of result
                    ++$result['count'];
                    // Stop the loop when the limit is reached
                    if ($result['count'] >= $param['limit']) {
                        break;
                    }
                }

                // Calculate the reference call
                $result['date_ref'] = $this->getReferenceCall($param, $result);
                if (empty($result['date_ref'])) {
                    throw new \Exception('Failed to get the reference call.');
                }
            } else {
                // Init values if no result
                $result['count'] = 0;
                $result['date_ref'] = $param['date_ref'];
            }
        } catch (\Exception $e) {
            $result['error'] = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
        }

        return $result;
    }

    /**
     * Get the new records from the solution
     * Param's content :
     *      date_ref : the oldest reference in the last call YYYY-MM-JJ hh:mm:ss
     *      module : module called
     *      fields : rule field list, example : array('name','date_entered')
     *      limit : max records that the rule can read (default limit is 100)
     * Expected output :
     *      Array with the list of records.
     */
    public function read(array $param): ?array
    {
        return null;
    }

    /**
     * Permet de créer un enregistrement
     * $param contient  :
     *  -> le module destinataire
     *  -> les données à envoyer sous cette forme :
     * Array
     * (
     *   [0] => Array
     *   (
     *      [id_sfaure01_001_target] => 52e58c482b704
     *      [name] => myddl01
     *      [email1] => myddle01@test.test
     *   )
     *   [1] => Array
     *   (
     *     [id_sfaure01_001_target] => 52e58c482baaa
     *     [name] => myddl02
     *     [email1] => myddle02@test.test
     *    )
     * )
     * Cette fonction retourne un tableau d'ID dans le même ordre que le tableau en entrée sous cette forme :
     * Array
     * (
     *   [0] => e1843994-10b6-09da-b2ab-52e58f6f7e57
     *   [1] => e3bc5d6a-f137-02ea-0f81-52e58fa5f75f
     * ).
     */
    public function createData(array $param): ?array
    {
        $result = [];
        try {
            // For every document
            foreach ($param['data'] as $idDoc => $record) {
                try {
                    // Clean record by removing myddleware field
                    $record = $this->cleanMyddlewareRecord($record);

                    // Check control before create
                    $record = $this->checkDataBeforeCreate($param, $record, $idDoc);
                    // Call create method
                    $recordId = $this->create($param, $record);

                    // Exception if no Id retruned
                    if (empty($recordId)) {
                        throw new \Exception('No Id returned. ');
                    }
                    // Format result
                    $result[$idDoc] = [
                        'id' => $recordId,
                        'error' => false,
                    ];
                } catch (\Exception $e) {
                    $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $error,
                    ];
                }
                // Status modification for the transfer
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $result[$idDoc] = [
                'id' => '-1',
                'error' => $error,
            ];
        }

        return $result;
    }

    /**
     * Create method :
     * - input : array with the record's data
     * - output : the id of the new record
     * An exception has to be generated when an error happends during the creation.
     * this exception will be caught by the createData method.
     */
    protected function create(array $param, $record)
    {
        return null;
    }

    /**
     * Permet de mettre à jour un enregistrement
     * Permet de créer un enregistrement
     * $param contient  :
     *  -> le module destinataire
     *  -> les données à envoyer sous cette forme (le champ id_target est obligatoire) :
     * Array
     * (
     *   [0] => Array
     *   (
     *     [target_id] => 54545-sds542s1d-sd21s2d54
     *     [id_sfaure01_001_target] => 52e58c482b704
     *     [name] => myddl01
     *     [email1] => myddle01@test.test
     *   )
     *   [1] => Array
     *   (
     *     [target_id] => 54545-sds542s1d-sd21s2d54
     *     [id_sfaure01_001_target] => 52e58c482baaa
     *     [name] => myddl02
     *     [email1] => myddle02@test.test
     *   )
     * )
     * Cette fonction retourne un tableau d'ID dans le même ordre que le tableau en entrée sous cette forme :
     * Array
     * (
     *   [0] => e1843994-10b6-09da-b2ab-52e58f6f7e57
     *   [1] => e3bc5d6a-f137-02ea-0f81-52e58fa5f75f
     * ).
     */
    public function updateData(array $param): array
    {
        $result = [];
        try {
            // For every document
            foreach ($param['data'] as $idDoc => $record) {
                try {
                    // Clean record by removing myddleware field
                    $record = $this->cleanMyddlewareRecord($record);

                    if (empty($record['target_id'])) {
                        throw new \Exception('No target id found. Failed to update the record.');
                    }
                    // Check control before create
                    $record = $this->checkDataBeforeUpdate($param, $record);
                    // Call create methode
                    $recordId = $this->update($param, $record);

                    // Exception if no Id retruned
                    if (empty($recordId)) {
                        throw new \Exception('No Id returned. ');
                    }
                    // Format result
                    $result[$idDoc] = [
                        'id' => $recordId,
                        'error' => false,
                    ];
                } catch (\Exception $e) {
                    $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $error,
                    ];
                }
                // Status modification for the transfer
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $result[$idDoc] = [
                'id' => '-1',
                'error' => $error,
            ];
        }

        return $result;
    }

    protected function update($param, $data)
    {
        return null;
    }

    public function deleteData(array $param): array
    {
        $result = [];

        try {
            // For every document
            foreach ($param['data'] as $idDoc => $record) {
                try {
                    if (empty($record['target_id'])) {
                        throw new \Exception('No target id found. Failed to update the record.');
                    }
                    // Check control before delete
                    $record = $this->checkDataBeforeDelete($param, $record);
                    // Call delete methode
                    $recordId = $this->delete($param, $record);

                    // Exception if no Id retruned
                    if (empty($recordId)) {
                        throw new \Exception('No Id returned. ');
                    }
                    // Format result
                    $result[$idDoc] = [
                        'id' => $recordId,
                        'error' => false,
                    ];
                } catch (\Exception $e) {
                    $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                    $result[$idDoc] = [
                        'id' => '-1',
                        'error' => $error,
                    ];
                }
                // Status modification for the transfer
                $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
            }
        } catch (\Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $result[$idDoc] = [
                'id' => '-1',
                'error' => $error,
            ];
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    protected function delete($param, $data)
    {
        // Set an error by default
        throw new \Exception('Delete method not developed for this connector. Failed to delete this record in the target application.');
    }

    /**
     * Permet de renvoyer le mode de la règle en fonction du module target
     * Valeur par défaut "0"
     * Si la règle n'est qu'en création, pas en modicication alors le mode est C.
     */
    public function getRuleMode($module, $type): array
    {
        return [
            '0' => 'create_modify',
            'C' => 'create_only',
        ];
    }

    public function setMessageCreateRule($module): void
    {
    }

    public function setApi($api): void
    {
        $this->api = $api;
    }

    // Permet d'ajouter des boutoon sur la page flux en fonction de la solution source ou targe
    // Type : source ou target
    public function getDocumentButton($type): array
    {
        return [];
    }

    // Permet d'indiquer le type de référence, si c'est une date (true) ou un texte libre (false)
    public function referenceIsDate($module): bool
    {
        return true;
    }

    // Permet de lancer l'action demandée dans la page flux
    public function documentAction($idDocument, $function)
    {
        return $this->$function($idDocument);
    }

    // Return if the read record button has to be display on the data transfert view
    public function getReadRecord(): bool
    {
        return $this->readRecord;
    }

    // Return if the connector can read deletion
    public function getReadDeletion($module): bool
    {
        return $this->readDeletion;
    }

    // Return if the connector can send deletion
    public function getSendDeletion($module): bool
    {
        return $this->sendDeletion;
    }

    /**
     * Permet de faire des contrôles dans Myddleware avant sauvegarde de la règle
     * Si le retour est false, alors la sauvegarde n'est pas effectuée et un message d'erreur est indiqué à l'utilisateur
     * data est de la forme :
     * [datereference] => 2015-02-23 00:00:00
     * [connector] => Array ( [source] => 3 [cible] => 30 )
     * [content] => Array (
     * [fields] => Array ( [name] => Array ( [Date] => Array ( [champs] => Array ( [0] => date_entered [1] => date_modified ) [formule] => Array ( [0] => {date_entered}.{date_modified} ) ) [account_Filter] => Array ( [champs] => Array ( [0] => name ) ) ) )
     * [params] => Array ( [mode] => 0 ) )
     * [relationships] => Array ( [0] => Array ( [target] => compte_Reference [rule] => 54ea64f1601fc [source] => Myddleware_element_id ) )
     * [module] => Array ( [source] => Array ( [solution] => sugarcrm [name] => Accounts ) [target] => Array ( [solution] => bittle [name] => oppt_multi7 ) )
     * La valeur de retour est de a forme : array('done'=>false, 'message'=>'message erreur');  ou array('done'=>true, 'message'=>'')
     * Le tableau de sortie peut aussi avoir une entrée params permettant d'indiquer l'ajout de paramètre à la règle.
     */
    public function beforeRuleSave($data, $type): array
    {
        return ['done' => true, 'message' => ''];
    }

    /**
     * Permet d'effectuer une action après la sauvegarde de la règle dans Myddleqare
     * Mêmes paramètres en entrée que pour la fonction beforeSave sauf que l'on a ajouté l'entrée ruleId au tableau
     * Retourne des message de type $messages[] = array ( 'type' => 'success', 'message' => 'OK');.
     */
    public function afterRuleSave($data, $type): array
    {
        return [];
    }

    // Fonction permettant de faire l'appel REST
    protected function call(string $url, string $method = 'GET', mixed $parameters = []): mixed
    {
        return null;
    }

    // Permet d'ajouter les champs obligatoires dans la listes des champs pour la lecture dans le système cible
    protected function addRequiredField($fields, $module = 'default', $mode = null)
    {
        // If no entry for the module we put default
        if (empty($this->requiredFields[$module])) {
            $module = 'default';
        }

        // Check $fields variable
        if (empty($fields)) {
            $fields = [];
        }

        // Boucle sur tous les champs obligatoires
        if (!empty($this->requiredFields[$module])) {
            foreach ($this->requiredFields[$module] as $required_field) {
                // Vérification de la présence du champs obligatoire
                $search_field = array_search($required_field, $fields);
                if (false === $search_field) {
                    $fields[] = $required_field;
                }
            }
        }

        // Add the ref field if it isn't already in the array
        $dateRefField = $this->getRefFieldName($module, $mode);
        if (
            !empty($dateRefField)
            and !in_array($dateRefField, $fields)
        ) {
            $fields[] = $dateRefField;
        }

        return $fields;
    }

    protected function addRequiredRelationship($module): void
    {
        if (!isset($this->requiredRelationships[$module])) {
            $this->requiredRelationships[$module] = [];
        }
        foreach ($this->requiredRelationships[$module] as $requiredRelationship) {
            if (!in_array($requiredRelationship, array_keys($this->moduleFields))) {
                $this->moduleFields[$requiredRelationship] = [
                    'label' => $requiredRelationship,
                    'type' => 'text',
                    'type_bdd' => 'varchar(255)',
                    'required' => false,
                    'required_relationship' => 1,
                    'relate' => true,
                ];
            } else {
                $this->moduleFields[$requiredRelationship]['required_relationship'] = 1;
            }
        }

        // Ajout systématique de l'id du module en cours dans les relation disponible : utile lorsque 2 modules source (2 règles) viennent mettre à jour 1 règle target
        if (empty($this->moduleFields['id'])) {
            $this->moduleFields['Myddleware_element_id'] = [
                'label' => 'ID '.$module,
                'type' => 'varchar(255)',
                'type_bdd' => 'varchar(255)',
                'required' => false,
                'required_relationship' => 0,
                'relate' => true,
            ];
        }
    }

    // Permet de supprimer l'élement Myddleware_element_id ajouter artificiellement dans un tableau de champ
    protected function cleanMyddlewareElementId($fieldArray)
    {
        if (!empty($fieldArray)) {
            $fieldArray = array_diff($fieldArray, ['Myddleware_element_id'], ['my_value']);
        }

        return $fieldArray;
    }

    // Clean record before create/update
    protected function cleanMyddlewareRecord(array $record): array
    {
        if (isset($record['Myddleware_element_id'])) {
            unset($record['Myddleware_element_id']);
        }

        return $record;
    }

    // Calculate the date modified of the current record
    protected function getModifiedDate($param, $record, $dateRefField): string
    {
        return $this->dateTimeToMyddleware($record[$dateRefField]);
    }

    // Function de conversion de datetime format solution à un datetime format Myddleware
    protected function dateTimeToMyddleware(string $dateTime): string
    {
        return $dateTime;
    }

    // Function de conversion de datetime format Myddleware à un datetime format solution
    protected function dateTimeFromMyddleware(string $dateTime): string
    {
        return $dateTime;
    }

    /**
     * @throws Exception
     */
    protected function getInfoDocument($idDocument): array|bool
    {
        $connection = $this->getConn();
        $sqlParams = '	SELECT *
						FROM document 
							INNER JOIN rule
								 ON document.rule_id = Rule.id
								AND document.deleted = 0
						WHERE document.id = :id_doc';
        $stmt = $connection->prepare($sqlParams);
        $stmt->bindValue(':id_doc', $idDocument);
        $result = $stmt->executeQuery();

        return $result->fetchAssociative();
    }

    /**
     * @throws Exception
     */
    protected function getSourceId($idDoc)
    {
        // Récupération du source_id
        $sql = 'SELECT `source_id` FROM `document` WHERE `id` = :idDoc AND document.deleted = 0';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':idDoc', $idDoc);
        $result = $stmt->executeQuery();
        $sourceId = $result->fetchAssociative();

        return $sourceId['source_id'];
    }

    // Ajout de champ personnalisé dans la target ex : bittle
    public function getFieldMappingAdd($moduleTarget): bool
    {
        return false;
    }

    // Return the name of the field used for the reference
    public function getRefFieldName(string $moduleSource, $ruleMode): string
    {
        return '';
    }

    // Return the name of the field used for the id
    public function getIdName(string $module): string
    {
        return 'id';
    }

    // The function return true if we can display the column parent in the rule view, relationship tab
    public function allowParentRelationship(string $module): bool
    {
        if (
            !empty($this->allowParentRelationship)
            && in_array($module, $this->allowParentRelationship)
        ) {
            return true;
        }

        return false;
    }

    // Build the direct link to the record (used in data transfer view)
    // Type : source or target
    public function getDirectLink(Rule $rule, Document $document, string $type): ?string
    {
        return null;
    }

    // Get a connector param decrypted
    protected function getConnectorParam(Connector $connector, string $paramName)
    {
        // Get the connector params from the rule
        $connectorParams = $connector->getConnectorParams();
        if (!empty($connectorParams)) {
            foreach ($connectorParams as $connectorParam) {
                // Get the param requested
                if ($connectorParam->getName() == $paramName) {
                    // Instanciate object to decrypte data
                    $encrypter = new \Illuminate\Encryption\Encrypter(substr($this->parameterBagInterface->get('secret'), -16));

                    return $encrypter->decrypt($connectorParam->getValue());
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function checkDataBeforeCreate(array $param, array $data, $idDoc): ?array
    {
        // Exception if the job has been stopped manually
        $this->isJobActive($param);
        // Target_id isn't used in create method
        if (array_key_exists('target_id', $data)) {
            unset($data['target_id']);
        }

        return $data;
    }

    /**
     * @throws \Exception
     */
    protected function checkDataBeforeUpdate(array $param, array $data): ?array
    {
        // Exception if the job has been stopped manually
        $this->isJobActive($param);

        return $data;
    }

    /**
     * @throws \Exception
     */
    protected function checkDataBeforeDelete(array $param, array $data): ?array
    {
        // Exception if the job has been stopped manually
        $this->isJobActive($param);

        return $data;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    protected function isJobActive(array $param): void
    {
        $sqlJobDetail = 'SELECT * FROM job WHERE id = :jobId';
        $stmt = $this->connection->prepare($sqlJobDetail);
        $stmt->bindValue(':jobId', $param['jobId']);
        $result = $stmt->executeQuery();
        $job = $result->fetchAssociative(); // 1 row
        if (
            empty($job['status'])
            || 'Start' != $job['status']
        ) {
            throw new \Exception('The task has been manually stopped. ');
        }
    }

    /**
     * Permet de récupérer les paramètre de login afin de faire un login quand on ne vient pas de la classe rule.
     *
     * @throws Exception
     */
    public function getLoginParameters($connectorId): ?array
    {
        $sqlConnectorParams = 'SELECT id, conn_id, name, value
				FROM connectorparam 
				WHERE conn_id = :connId';
        $stmt = $this->connection->prepare($sqlConnectorParams);
        $stmt->bindValue('connId', $connectorId);
        $result = $stmt->executeQuery();
        $connectorParams = $result->fetchAllAssociative();

        $params = [];

        if (!empty($connectorParams)) {
            foreach ($connectorParams as $value) {
                $params[$value['name']] = $value['value'];
                $params['ids'][$value['name']] = ['id' => $value['id'], 'conn_id' => $value['conn_id']];
            }
        }

        return $params;
    }

    // Method de find the date ref after a read call
    protected function getReferenceCall($param, $result)
    {
        // Result is sorted, the last one is the oldest one
        return end($result['values'])['date_modified'];
    }

}
