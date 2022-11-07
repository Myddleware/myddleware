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

namespace App\Solutions;

use App\Manager\RuleManager as RuleMyddleware;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class sapcore extends saproot
{
    protected int $limit = 5;

    // Permet de connaître la clé de filtrage principale sur les tables, la fonction partenire sur la table des partenaire par exemple
    // ces filtres correspondent aux sélections de l'utilisateur lors de la création de règle
    /* protected $keySubStructure = array('FI_DOCUMENT' => array(
                                                        // 'ET_PARTNER' => 'PARTNER_FCT',
                                                        // 'ET_STATUS'  => 'USER_STAT_PROC',
                                                        // 'ET_APPOINTMENT' => 'APPT_TYPE'
                                                        ),
                                        );

    // Permet d'ajouter des filtres sur les tables, on ne prend que les partenaires principaux sur la table des partenaire par exemple
    protected $subStructureFilter = array('FI_DOCUMENT' => array(
                                                            // 'ET_PARTNER' => array('MAINPARTNER' => 'X')
                                                        ),
                                        );
    */
    protected array $guidName = ['ET_BKPF' => [
        'ET_BKPF' => 'BELNR',
        'ET_BSEG' => 'BELNR',
        'ET_ABUZ' => 'BELNR',
        'ET_ACCHD' => 'AWREF',
        'ET_ACCCR' => 'AWREF',
        'ET_ACCIT' => 'AWREF',
    ],
        'BU_PARTNER' => [
            'ET_BUT000' => 'PARTNER_GUID',
        ],
    ];

    protected array $required_fields = [
        'ET_BKPF' => ['ET_BKPF__BELNR', 'ET_BKPF__PSODT', 'ET_BKPF__PSOTM'],
    ];

    protected array $relateFieldAllowed = [
        'ET_BSEG' => [
            'KUNNR' => ['label' => 'Partner number', 'required_relationship' => false],
        ],
    ];

    // Permet d'indiquer quels champs génères l'id de chaque module
    protected $buildId = [
        'ET_BKPF' => ['Mandt', 'Bukrs', 'Belnr', 'Gjahr'],
        'ET_BSEG' => ['Mandt', 'Bukrs', 'Belnr', 'Gjahr', 'Buzei'],
    ];

    public function login($paramConnexion)
    {
        $paramConnexion['wsdl'] = __DIR__.'/../Custom/Solutions/sap/wsdl/'.$paramConnexion['wsdl'];
        parent::login($paramConnexion);
    }

    // Renvoie les modules disponibles du compte Salesforce connecté
    public function get_modules($type = 'source'): array
    {
        return [
            'ET_BKPF' => 'FI En-tête pièce pour comptabilité (ET_BKPF)',
            'ET_BSEG' => 'FI Segment de pièce comptabilité (ET_BSEG)',
            'ET_ABUZ' => 'FI Lignes d\'écriture générées automatiquement (ET_ABUZ)',
            'ET_ACCHD' => 'FI Table transgert infos d\'en-tête pr documents FI-CO (ET_ACCHD)',
            'ET_ACCCR' => 'FI Interface ds la gestion comptable : information devise (ET_ACCCR)',
            'ET_ACCIT' => 'FI Interface avec la gestion comptable : information poste (ET_ACCIT)',
        ];
    }

    // On appelle la fonction get_module_fields de SAP standard et on ajoute les champ de relation spécifique
    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        // Le champ relate ET_BKPF est ajouté sur le module ET_BSEG, relation obligatoire. Le module ET_BSEG n'a pas lieu d'être sans le module ET_BKPF car c'est lui qui lui génère les documents
        if ('ET_BSEG' == $module) {
            $this->moduleFields['ET_BKPF'] = [
                'label' => 'FI En-tête pièce pour comptabilité (ET_BKPF)',
                'type' => 'varchar(255)',
                'type_bdd' => 'varchar(255)',
                'required_relationship' => true,
            ];
        }

        return parent::get_module_fields($module, $type);
    }

    // Permet d'ajouter des règles en relation si les règles de gestion standard ne le permettent pas
    // Par exemple si on veut connecter des règles de la solution SAP CRM avec la solution SAP qui sont 2 solutions différentes qui peuvent être connectées
    public function get_rule_custom_relationship($module, $type)
    {
        // Si module est ET_BSEG alors on autorise les règles PARTNER de SAP CRM
        if ('source' == $type) {
            if ('ET_BSEG' == $module) {
                $sql = "SELECT 
							Rule.id, 
							Rule.name, 
							Rule.version 
						FROM Rule
						WHERE
								Rule.deleted = 0
							AND Rule.module_source = 'BU_PARTNER'";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':idHeaderRule', $param['rule']['id']);
                $result = $stmt->executeQuery();
                $rules = $result->fetchAllAssociative();
                if (!empty($rules)) {
                    return $rules;
                }
            }
        }
    }

    public function getFieldsParamUpd($type, $module): array
    {
        try {
            $params = [];
            if ('source' == $type) {
                // Ajout du paramètre de l'exercice comptable obligatoire pour FI
                if (in_array($module, ['ET_BKPF'])) {
                    $gjahrParam = [
                        'id' => 'GJAHR',
                        'name' => 'GJAHR',
                        'type' => 'option',
                        'label' => 'Fiscal Year',
                        'required' => true,
                    ];
                    $currentYear = date('Y');
                    // On prend 10 ans d'exercice comptable
                    for ($i = $currentYear - 9; $i <= $currentYear + 1; ++$i) {
                        $gjahrParam['option'][$i] = $i;
                    }
                    $params[] = $gjahrParam;
                }

                // Ajout du paramètre correspondant à la société
                if (in_array($module, ['ET_BKPF'])) {
                    $bukrsParam = [
                        'id' => 'BUKRS',
                        'name' => 'BUKRS',
                        'type' => TextType::class,
                        'label' => 'Company Code',
                        'required' => true,
                    ];
                    $params[] = $bukrsParam;
                }
            }

            return $params;
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

            return [];
        }
    }

    // Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
    public function readData($param)
    {
        // Initialisation de la limit
        if (empty($param['limit'])) {
            $param['limit'] = $this->limit;
        }
        // Conversion de la date ref au format SAP
        if (!empty($param['date_ref'])) {
            $param['date_ref_format'] = $this->dateTimeFromMyddleware($param['date_ref']);
        }

        if ('ET_BKPF' == $param['module']) {
            $parameters = [
                'IvDateRef' => $param['date_ref_format'],
                // 'IvLimit' => 5,
                'IvLimit' => $param['limit'],
                'IvBelnr' => '',
                'IvBukrs' => $param['ruleParams']['BUKRS'],
                'IvGjahr' => $param['ruleParams']['GJAHR'],
                'IvTypeDate' => ('C' == $param['ruleParams']['mode'] ? 'C' : 'U'),
            ];

            return $this->readFiDocument($param, $parameters, false);
        }
        // Pas de lecture pour les autres modules FI, tout est lu via le module ET_BKPF et ses règles liées
        elseif (in_array($param['module'], ['ET_BSEG', 'ET_ABUZ', 'ET_ACCHD', 'ET_ACCCR', 'ET_ACCIT'])) {
            return;
        }
    }

    // Permet de lire les document FI
    // C'est une règle particulière car elle peut générer de document fils sur d'autres règles
    public function readFiDocument($param, $parameters, $readLast): array
    {
        try {
            try {
                // Erreur s'il manque des données
                if (!$readLast) {
                    if (empty($param['ruleParams']['BUKRS'])) {
                        throw new \Exception('Failed to read data. No company code.');
                    }
                    if (empty($param['ruleParams']['GJAHR'])) {
                        throw new \Exception('Failed to read data. No fiscal year.');
                    }
                }

                // Ajout des champs obligatoires
                $param['fields'] = $this->addRequiredField($param['fields'], $param['module'], $param['ruleParams']['mode']);
                // Permet de supprimer l'élement Myddleware_element_id ajouter artificiellement dans un tableau de champ
                $param['fields'] = $this->cleanMyddlewareElementId($param['fields']);
                // Tri des champs pour optimiser la performance dans la recherche des données
                arsort($param['fields']);
                // Réupération des données dans SAP
                $response = $this->client->ZmydSearchFiDocument($parameters);

                if ('E' == $response->EvTypeMessage) {
                    throw new \Exception('Read FI document failed : '.$response->EvTypeMessage);
                }

                if ($response->EvCount > 0) {
                    // Si on a qu'un seule données en sortie on la transforme en tableau pour que le code suivant reste compatible
                    if (!is_array($response->EtFiDocument->item)) {
                        $documents[] = $response->EtFiDocument->item;
                    } else {
                        $documents = $response->EtFiDocument->item;
                    }
                    foreach ($documents as $document) {
                        $record['date_modified'] = '';
                        // Sauvegarde du document header BKPF
                        foreach ($param['fields'] as $field) {
                            // Le champ est sous la forme module__field
                            $fieldDetails = explode('__', $field);
                            // Transformation du nom de la structure (exemple ET_ORDERADM_H devient EtOrderadmH)
                            $fieldName = $this->transformName($fieldDetails[1]);
                            $record[$field] = $document->Bkpf->item->$fieldName;

                            // On ajoute la date de modification
                            // Pour document, l'heure et la date sont dans 2 champs différents
                            if ('Cpudt' == $fieldName) {
                                $record['date_modified'] = $document->Bkpf->item->$fieldName.$record['date_modified'];
                            } elseif ('Cputm' == $fieldName) {
                                $record['date_modified'] = $record['date_modified'].' '.$document->Bkpf->item->$fieldName;
                            }
                        }
                        // Si toujours plusieurs erreur même après filtrage alors on envoi un warning
                        if (!empty($document->EvMessage)) {
                            $record['ZmydMessage'] = ['type' => $document->EvTypeMessage, 'message' => $document->EvMessage];
                        }
                        // Constrution de l'id
                        $record['id'] = $this->generateId($param['module'], $document->Bkpf->item);

                        // Sauvegarde des données du document
                        $result['values'][$record['id']] = $record;

                        // Pour chaque document d'entête on génère les document fils sur chaque règles liées
                        // Récupération des règles liées à la règle actuelle
                        // Récupération de toutes les règles avec l'id connector en cours qui sont root et qui ont au moins une référence
                        $sql = 'SELECT DISTINCT
									Rule.id,
									Rule.module_source,
									Rule.name_slug
								FROM RuleRelationShip
									INNER JOIN Rule
										ON Rule.id = RuleRelationShip.rule_id
								WHERE
										Rule.deleted = 0
									AND RuleRelationShip.field_id = :idHeaderRule';
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bindValue(':idHeaderRule', $param['rule']['id']);
                        $result = $stmt->executeQuery();
                        $rules = $result->fetchAllAssociative();
                        if (!empty($rules)) {
                            // Pour chaque règle liée on récupérère les données et on génère les documents fils
                            foreach ($rules as $rule) {
                                // Calcul du nom du module (exemple ET_BSEG devien Bseg)
                                $moduleDetails = explode('_', $rule['module_source'], 2);
                                $moduleName = $this->transformName($moduleDetails[1]);
                                if (!is_array($document->$moduleName->item)) {
                                    $childData[] = $document->$moduleName->item;
                                } else {
                                    $childData = $document->$moduleName->item;
                                }
                                if (!empty($childData)) {
                                    // Si le module de la règle est présent dans la réponse du webservice, on génère l'objet règle
                                    $param['ruleId'] = $rule['id'];
                                    $ruleMyddleware = new ruleMyddleware($this->logger, $this->container, $this->conn, $param);
                                    // Pour toutes les lignes du module fils on génère un document fils
                                    foreach ($childData as $childDocument) {
                                        $data = '';
                                        // Mise en forme des données en reconstruisant le nom du champ
                                        foreach ($childDocument as $key => $value) {
                                            // On enlève les '0' avant le numéro de partenaire
                                            if ('Kunnr' == $key) {
                                                $value = ltrim($value, '0');
                                            }
                                            $data['values'][$rule['module_source'].'__'.strtoupper($key)] = $value;
                                        }
                                        // Ajout des champs obligatoire date_modufied en id
                                        $data['values']['date_modified'] = $record['date_modified'];
                                        $data['values']['id'] = $this->generateId($rule['module_source'], $childDocument);
                                        // Ajout de l'id du module d'en-tête
                                        $data['values']['ET_BKPF'] = $record['id'];
                                        $generateDocuments = $ruleMyddleware->generateDocuments($record['id'], false, $data);
                                        if (!empty($generateDocuments->error)) {
                                            $record['ZmydMessage'] = ['type' => 'E', 'message' => 'Failed to create child document ('.$rule['module_source'].') '.$generateDocuments->error];
                                        }
                                    }
                                }
                            }
                        }
                        $record = '';
                    }
                }
                $result['count'] = $response->EvCount;
                $result['date_ref'] = $this->dateTimeToMyddleware($response->EvDateRef);

                // Si readLast alors on change le format des données de sortie
                if ($readLast) {
                    $result = [];
                    if (!empty($record)) {
                        $result['values'] = $record;
                        $result['done'] = true;
                    } else {
                        $result['done'] = false;
                    }
                }

                return $result;
            } catch (\SoapFault $fault) {
                if (!empty($fault->getMessage())) {
                    throw new \Exception($fault->getMessage());
                }
                throw new \Exception('SOAP FAULT. Read order failed.');
            }
        } catch (\Exception $e) {
            $error = 'Failed to read FI document from sapcrm : '.$e->getMessage().' '.__CLASS__.' Line : '.$e->getLine().'. ';
            echo $error.';';
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    // Permet de generer un id en fonction des champs du module

    /**
     * @throws \Exception
     */
    protected function generateId($module, $data): string
    {
        if (!empty($this->buildId[$module])) {
            $id = '';
            // Construction de l'id
            foreach ($this->buildId[$module] as $field) {
                if (!empty($data->$field)) {
                    $id .= $data->$field.'_';
                } else {
                    $id .= '_';
                }
            }
            if (!empty($id)) {
                $id = rtrim($id, '_');
            }
            if (empty($id)) {
                throw new \Exception('Failed to generate id. Id is empty.');
            }

            return $id;
        }

        throw new \Exception('Failed to generate id for the module '.$module.'. No table for id.');
    }

    public function getRuleMode($module, $type): array
    {
        // Pour l'instant tout est create only
        if ('target' == $type) {
            return [
                'C' => 'create_only',
            ];
        }

        return parent::getRuleMode($module, $type);
    }
}

class sap extends sapcore
{
}
