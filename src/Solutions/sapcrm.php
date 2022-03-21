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

use Symfony\Component\Form\Extension\Core\Type\TextType;

//use Psr\LoggerInterface;

class sapcrmcore extends saproot
{
    // Permet de connaître la clé de filtrage principale sur les tables, la fonction partenire sur la table des partenaire par exemple
    // ces filtres correspondent aux sélections de l'utilisateur lors de la création de règle
    protected $keySubStructure = ['CRMD_ORDER' => [
        'ET_PARTNER' => 'PARTNER_FCT',
        'ET_STATUS' => 'USER_STAT_PROC',
        'ET_APPOINTMENT' => 'APPT_TYPE',
    ],
        'BU_PARTNER' => [
            'ET_BAPIADTEL' => 'STD_NO',
            'ET_BAPIADFAX' => 'STD_NO',
            'ET_BAPIADSMTP' => 'STD_NO',
            'ET_BAPIADURI' => 'STD_NO',
        ],
    ];

    // Permet d'ajouter des filtres sur les tables, on ne prend que les partenaires principaux sur la table des partenaire par exemple
    protected $subStructureFilter = ['CRMD_ORDER' => [
        'ET_PARTNER' => ['MAINPARTNER' => 'X'],
    ],
    ];

    // Permet d'avoir les GUID pour chaque sous-structure et ainsi de savoirà quel partner/order... appartiennent les autre sstructures
    protected $guidName = ['CRMD_ORDER' => [
        'ET_ORDERADM_H' => 'GUID',
        'ET_ACTIVITY_H' => 'GUID',
        'ET_STATUS' => 'GUID',
    ],
        'BU_PARTNER' => [
            'ET_BUT000' => 'PARTNER',
            'default' => 'PARTNER',
        ],
    ];

    // Permet d'indiquer quel est l'id pour chaque module
    protected $idName = [
        'CRMD_ORDER' => ['ET_ORDERADM_H' => 'OBJECT_ID'],
        'BU_PARTNER' => ['ET_BUT000' => 'PARTNER'],
    ];

    protected $required_fields = [
        'CRMD_ORDER' => ['ET_ORDERADM_H__CHANGED_AT', 'ET_ORDERADM_H__OBJECT_ID'],
        'BU_PARTNER' => ['ET_BUT000__CHDAT', 'ET_BUT000__CHTIM', 'ET_BUT000__PARTNER'],
    ];

    protected $relateFieldAllowed = [
        'CRMD_ORDER' => [
            // 'ET_ORDERADM_H' => array('OBJECT_ID'=> array('label' =>  'Object ID','required_relationship' => false)),
            'ET_PARTNER' => ['PARTNER_NO' => ['label' => 'Partner number', 'required_relationship' => false]],
        ],
        // 'BU_PARTNER' => array(
        // 'ET_BUT000029PCFR' => array('PARTNER2'=> array('label' =>  'Agence','required_relationship' => false))
        // )
    ];

    public function login($paramConnexion)
    {
        $paramConnexion['wsdl'] = __DIR__.'/../Custom/Solutions/sapcrm/wsdl/'.$paramConnexion['wsdl'];
        parent::login($paramConnexion);
    }

    // login($paramConnexion)*/

    // Renvoie les modules disponibles du compte Salesforce connecté
    public function get_modules($type = 'source')
    {
        if ($type = 'source') {
            return [
                'CRMD_ORDER' => 'Order',
                'BU_PARTNER' => 'Partner',
            ];
        }

        return [
            // 'CRMD_ORDER' => 'Order',
            'BU_PARTNER' => 'Partner',
        ];
    }

    // get_modules()

    public function get_module_fields($module, $type = 'source', $param = null)
    {
        if ('target' == $type) {
            switch ($module) {
                case 'BU_PARTNER':
                    $this->moduleFields = [
                        'HEADER__CATEGORIE' => ['label' => 'CATEGORIE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__TYPE'],
                        'HEADER__GROUP' => ['label' => 'GROUP', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BU_GROUP'],
                        'HEADER__BUSINESSPARTNEREXTERN' => ['label' => 'BUSINESSPARTNEREXTERN', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BPEXT'],
                        'CENTRALDATA__PARTNERTYPE' => ['label' => 'PARTNERTYPE', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0, 'readName' => 'ET_BUT000__BPKIND'],
                        'CENTRALDATA__TITLE_KEY' => ['label' => 'TITLE_KEY', 'type' => TextType::class, 'type_bdd' => 'text', 'required' => 0, 'readName' => 'ET_BUT000__TITLE'],
                        'CENTRALDATAPERSON__FIRSTNAME' => ['label' => 'FIRSTNAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_FIRST'],
                        'CENTRALDATAPERSON__LASTNAME' => ['label' => 'LASTNAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_LAST'],
                        'CENTRALDATAPERSON__MIDDLENAME' => ['label' => 'MIDDLENAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAMEMIDDLE'],
                        'CENTRALDATAPERSON__TITLE_ACA1' => ['label' => 'TITLE_ACA1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__TITLE_ACA1'],
                        'CENTRALDATAPERSON__TITLE_ACA2' => ['label' => 'TITLE_ACA2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__TITLE_ACA2'],
                        'CENTRALDATAPERSON__NICKNAME' => ['label' => 'NICKNAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NICKNAME'],
                        'CENTRALDATAPERSON__BIRTHPLACE' => ['label' => 'BIRTHPLACE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BIRTHPL'],
                        'CENTRALDATAPERSON__BIRTHDATE' => ['label' => 'BIRTHDATE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BIRTHDT'],
                        'CENTRALDATAPERSON__MARITALSTATUS' => ['label' => 'MARITALSTATUS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__MARST'],
                        'CENTRALDATAPERSON__EMPLOYER' => ['label' => 'EMPLOYER', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__EMPLO'],
                        'CENTRALDATAPERSON__NATIONALITY' => ['label' => 'NATIONALITY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NATIO'],
                        'CENTRALDATAPERSON__NATIONALITYISO' => ['label' => 'NATIONALITYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAMCOUNTRY'],
                        'CENTRALDATAORGANIZATION__NAME1' => ['label' => 'NAME1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG1'],
                        'CENTRALDATAORGANIZATION__NAME2' => ['label' => 'NAME2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG2'],
                        'CENTRALDATAORGANIZATION__NAME3' => ['label' => 'NAME3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG3'],
                        'CENTRALDATAORGANIZATION__NAME4' => ['label' => 'NAME4', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG4'],
                        'CENTRALDATAORGANIZATION__INDUSTRYSECTOR' => ['label' => 'INDUSTRYSECTOR', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__IND_SECTOR'],
                        'CENTRALDATAGROUP__INAMEGROUP1' => ['label' => 'NAMEGROUP1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_GRP1'],
                        'CENTRALDATAGROUP__INAMEGROUP2' => ['label' => 'NAMEGROUP2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_GRP2'],
                        'CENTRALDATAGROUP__GROUPTYPE' => ['label' => 'GROUPTYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__PARTGRPTYP'],
                        'ADDRESSDATA__STANDARDADDRESS' => ['label' => 'STANDARDADDRESS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STANDARDADDRESS'],
                        'ADDRESSDATA__C_O_NAME' => ['label' => 'C_O_NAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__C_O_NAME'],
                        'ADDRESSDATA__CITY' => ['label' => 'CITY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__CITY'],
                        'ADDRESSDATA__POSTL_COD1' => ['label' => 'POSTL_COD1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__CITY'],
                        'ADDRESSDATA__PO_BOX' => ['label' => 'PO_BOX', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__PO_BOX'],
                        'ADDRESSDATA__PO_CTRYISO' => ['label' => 'PO_CTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__PO_CTRYISO'],
                        'ADDRESSDATA__STREET' => ['label' => 'STREET', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STREET'],
                        'ADDRESSDATA__HOUSE_NO' => ['label' => 'HOUSE_NO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__HOUSE_NO'],
                        'ADDRESSDATA__STR_SUPPL1' => ['label' => 'STR_SUPPL1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STR_SUPPL1'],
                        'ADDRESSDATA__STR_SUPPL2' => ['label' => 'STR_SUPPL2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STR_SUPPL2'],
                        'ADDRESSDATA__STR_SUPPL3' => ['label' => 'STR_SUPPL3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STR_SUPPL3'],
                        'ADDRESSDATA__BUILDING' => ['label' => 'BUILDING', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__BUILDING'],
                        'ADDRESSDATA__FLOOR' => ['label' => 'FLOOR', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__FLOOR'],
                        'ADDRESSDATA__ROOM_NO' => ['label' => 'ROOM_NO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__ROOM_NO'],
                        'ADDRESSDATA__COUNTRY' => ['label' => 'COUNTRY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__COUNTRY'],
                        'ADDRESSDATA__COUNTRYISO' => ['label' => 'COUNTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__COUNTRYISO'],
                        'ADDRESSDATA__REGION' => ['label' => 'REGION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__REGION'],
                        'TELEFONDATA__TELEPHONE' => ['label' => 'TELEPHONE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADTEL__X__TELEPHONE'],
                        'TELEFONDATA__COUNTRYISO' => ['label' => 'COUNTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADTEL__X__COUNTRYISO'],
                        'FAXDATA__FAX' => ['label' => 'FAX', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADFAX__X__FAX'],
                        'FAXDATA__COUNTRYISO' => ['label' => 'COUNTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADFAX__X__COUNTRYISO'],
                        'E_MAILDATA__E_MAIL' => ['label' => 'E_MAIL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADSMTP__X__E_MAIL'],
                        'URIADDRESSDATA__URI' => ['label' => 'URI', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADURI__X__URI'],
                        'URIADDRESSDATA__URI_TYPE' => ['label' => 'URI_TYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADURI__X__URI_TYPE'],
                    ];
                    break;
                case 'CRMD_ORDER':
                    $this->moduleFields = [
                        'ORDERADM_H__PROCESS_TYPE' => ['label' => 'PROCESS_TYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'readName' => 'ET_ORDERADM_H__PROCESS_TYPE'],
                        'ORDERADM_H__POSTING_DATE' => ['label' => 'POSTING_DATE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ORDERADM_H__POSTING_DATE'],
                        'ORDERADM_H__DESCRIPTION' => ['label' => 'DESCRIPTION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ORDERADM_H__DESCRIPTION'],
                        'STATUS__ZSPIR_R1__STATUS' => ['label' => 'STATUS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_STATUS__ZSPIR_R1__STATUS'],
                        'STATUS__ZSPIR_R1__USER_STAT_PROC' => ['label' => 'USER_STAT_PROC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_STATUS__ZSPIR_R1__USER_STAT_PROC'],
                        'ACTIVITY_H__NAME' => ['label' => 'NAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__NAME'],
                        'ACTIVITY_H__CATEGORY' => ['label' => 'CATEGORY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__CATEGORY'],
                        'ACTIVITY_H__PRIORITY' => ['label' => 'PRIORITY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__DESCRIPTION'],
                        'ACTIVITY_H__OBJECTIVE' => ['label' => 'OBJECTIVE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__OBJECTIVE'],
                        'ACTIVITY_H__DIRECTION' => ['label' => 'DIRECTION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__DIRECTION'],
                        'ACTIVITY_H__PRIVATE_FLAG' => ['label' => 'PRIVATE_FLAG', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__PRIVATE_FLAG'],
                        'ACTIVITY_H__COMPLETION' => ['label' => 'COMPLETION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__COMPLETION'],
                        'ACTIVITY_H__ACT_LOCATION' => ['label' => 'ACT_LOCATION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__ACT_LOCATION'],
                        'APPOINTMENT__ORDERPLANNED__APPT_TYPE' => ['label' => 'ORDERPLANNED__APPT_TYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__APPT_TYPE'],
                        'APPOINTMENT__ORDERPLANNED__TIMESTAMP_FROM' => ['label' => 'ORDERPLANNED__TIMESTAMP_FROM', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMESTAMP_FROM'],
                        'APPOINTMENT__ORDERPLANNED__TIMEZONE_FROM' => ['label' => 'ORDERPLANNED__TIMEZONE_FROM', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMESTAMP_TO'],
                        'APPOINTMENT__ORDERPLANNED__TIMESTAMP_TO' => ['label' => 'ORDERPLANNED__TIMESTAMP_TO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMESTAMP_TO'],
                        'APPOINTMENT__ORDERPLANNED__TIMEZONE_TO' => ['label' => 'ORDERPLANNED__TIMEZONE_TO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMEZONE_TO'],
                        'APPOINTMENT__ORDERPLANNED__DOMINANT' => ['label' => 'ORDERPLANNED__DOMINANT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__DOMINANT'],
                        'APPOINTMENT__ORDERPLANNED__DURATION' => ['label' => 'ORDERPLANNED__DURATION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__DURATION'],
                        'PARTNER__00000009__PARTNER_NO' => ['label' => '00000009__PARTNER_NO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'required_relationship' => 0, 'readName' => 'ET_PARTNER__00000009__PARTNER_NO', 'relate' => false],
                    ];
                    break;
            }

            return $this->moduleFields;
        }

        return parent::get_module_fields($module, $type);
    }

    // Permet de modifier les nom des champs pour le read_last
    // Dans SAP les champs en lecture et en écriture ne sont pas toujours identiques pour le même module, les structures peuvent être différentes
    protected function convertFieldReadLast($param, $values, $mode)
    {
        try {
            $this->get_module_fields($param['module'], 'target');
            if (!empty($this->moduleFields)) {
                if (!empty($param['fields'])) {
                    $convertFields = [];
                    foreach ($param['fields'] as $field) {
                        // Si on a pas de données alors on a à mettre les noms de champ attendus par le SEARCH_BP de SAP
                        if ('1' == $mode) {
                            if (!empty($this->moduleFields[$field]['readName'])) {
                                $convertFields[] = $this->moduleFields[$field]['readName'];
                            } else {
                                throw new \Exception('The field '.$field.' has no readName. Failed to read data in SAP CRM. ');
                            }
                        }

                        // Sinon c'est que l'on a eu le retour de SAP et que l'on doit remettre les nom d'orgine des champs
                        elseif ('2' == $mode) {
                            if (
                                    !empty($this->moduleFields[$field]['readName'])
                                && !empty($values[$this->moduleFields[$field]['readName']])
                            ) {
                                $convertFields[$field] = $values[$this->moduleFields[$field]['readName']];
                            }
                        }
                    }
                    // Dans le cas où on transforme le retour de SAP on ajoute l'id dans le tableau corrigé
                    if (!empty($values)) {
                        $convertFields['id'] = $values['id'];
                    }
                }
            }

            return ['done' => 1, 'fields' => $convertFields];
        } catch (\Exception $e) {
            $error = 'Failed to read the record from sapcrm : '.$e->getMessage().' '.__CLASS__.' Line : '.$e->getLine().'. ';

            return ['done' => -1, 'error' => $error];
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

        if ('CRMD_ORDER' == $param['module']) {
            $parameters = [
                'IvDateRef' => $param['date_ref_format'],
                'IvLimit' => $param['limit'],
                'IvObjectId' => '',
                'IvObjectType' => '',
                'IvProcessType' => '',
                'IvTypeDate' => ('C' == $param['ruleParams']['mode'] ? 'C' : 'U'),
            ];
            // return $this->readOrder($param,false);
            return $this->readMultiStructure($param, 'ZmydSearchOrders', $parameters, false);
        }
        if ('BU_PARTNER' == $param['module']) {
            $parameters = [
                'IvDateRef' => $param['date_ref_format'],
                'IvLimit' => $param['limit'],
                // 'IvLimit' => 100,
                'IvParner' => '',
                'IvBuGroup' => (empty($param['ruleParams']['BU_GROUP']) ? '' : $param['ruleParams']['BU_GROUP']),
                'IvSalesOrg' => (empty($param['ruleParams']['SALES_ORG']) ? '' : $param['ruleParams']['SALES_ORG']),
                'IvTypeDate' => ('C' == $param['ruleParams']['mode'] ? 'C' : 'U'),
            ];

            return $this->readMultiStructure($param, 'ZmydSearchBp', $parameters, false);
        }
    }

    // Permet de créer des données
    public function createData($param)
    {
        // Transformation du tableau d'entrée pour être compatible webservice SAP CRM
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before create
                $data = $this->checkDataBeforeCreate($param, $data, $idDoc);
                $dataSugar = [];
                foreach ($data as $key => $value) {
                    $tabValue = explode('__', $key);
                    if (
                            empty($tabValue[0])
                         || empty($tabValue[1])
                    ) {
                        $result[$idDoc] = [
                            'id' => $idDoc,
                            'error' => 'The field '.$key.' is not compatible. No caractere __ inside the name of this field.',
                        ];

                        break;
                    }
                    // On mets dans ZzmydKey tous les éléments sauf la structure (le +2 permet de e pas mettre les 2 _)
                    $dataSapCrm[] = ['ZzmydKey' => substr($key, strlen($tabValue[0]) + 2), 'ZzmydValue' => $value, 'ZzmydType' => '', 'ZzmydLabel' => $tabValue[0]];
                }
                $parameters = [
                    'ItData' => $dataSapCrm,
                ];
                if ('BU_PARTNER' == $param['module']) {
                    $response = $this->client->ZmydCreateBp($parameters);
                    if ('E' == $response->EvTypeMessage) {
                        throw new \Exception('Failed to create partner : '.utf8_encode($response->EvMessage).'.');
                    }
                    if (!empty($response->EvPartner)) {
                        $result[$idDoc] = [
                            'id' => $response->EvPartner,
                            'error' => false,
                        ];
                    } else {
                        $result[$idDoc] = [
                            'id' => -1,
                            'error' => '01',
                        ];
                    }
                } elseif ('CRMD_ORDER' == $param['module']) {
                    $response = $this->client->ZmydCreateOrder($parameters);
                    if ('E' == $response->EvTypeMessage) {
                        throw new \Exception('Failed to create order : '.utf8_encode($response->EvMessage).'.');
                    }
                    if (!empty($response->EvObjectId)) {
                        $result[$idDoc] = [
                            'id' => $response->EvObjectId,
                            'error' => false,
                        ];
                    } else {
                        $result[$idDoc] = [
                            'id' => -1,
                            'error' => '01',
                        ];
                    }
                } else {
                    throw new \Exception('Module '.$param['module'].' unknown for create function. ');
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => -1,
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Permet de créer des données
    public function updateData($param)
    {
        // Transformation du tableau d'entrée pour être compatible webservice SAP CRM
        foreach ($param['data'] as $idDoc => $data) {
            try {
                // Check control before update
                $data = $this->checkDataBeforeUpdate($param, $data);
                $dataSugar = [];
                // Récupération des modifications seulement
                $diff = array_diff($data, $param['dataHistory'][$idDoc]);
                foreach ($diff as $key => $value) {
                    $tabValue = explode('__', $key);
                    if (
                            empty($tabValue[0])
                         || empty($tabValue[1])
                    ) {
                        $result[$idDoc] = [
                            'id' => $idDoc,
                            'error' => 'The field '.$key.' is not compatible. No caractere __ inside the name of this field.',
                        ];
                        break;
                    }
                    // On mets dans ZzmydKey tous les éléments sauf la structure (le +2 permet de e pas mettre les 2 _)
                    $dataSapCrm[] = ['ZzmydKey' => substr($key, strlen($tabValue[0]) + 2), 'ZzmydValue' => $value, 'ZzmydType' => '', 'ZzmydLabel' => $tabValue[0]];
                }

                if ('BU_PARTNER' == $param['module']) {
                    $parameters = [
                        'ItData' => $dataSapCrm,
                        'EvPartner' => $data['target_id'],
                    ];
                    $response = $this->client->ZmydUpdateBp($parameters);
                    if ('E' == $response->EvTypeMessage) {
                        throw new \Exception('Failed to update partner : '.utf8_encode($response->EvMessage).'.');
                    }
                    if (!empty($response->EvPartner)) {
                        $result[$idDoc] = [
                            'id' => $response->EvPartner,
                            'error' => false,
                        ];
                    } else {
                        $result[$idDoc] = [
                            'id' => -1,
                            'error' => '01',
                        ];
                    }
                } elseif ('CRMD_ORDER' == $param['module']) {
                    $parameters = [
                        'ItData' => $dataSapCrm,
                        'EvObjectId' => $data['target_id'],
                    ];
                    $response = $this->client->ZmydUpdateOrder($parameters);
                    if ('E' == $response->EvTypeMessage) {
                        throw new \Exception('Failed to update order : '.utf8_encode($response->EvMessage).'.');
                    }
                    if (!empty($response->EvObjectId)) {
                        $result[$idDoc] = [
                            'id' => $response->EvObjectId,
                            'error' => false,
                        ];
                    } else {
                        $result[$idDoc] = [
                            'id' => -1,
                            'error' => '01',
                        ];
                    }
                } else {
                    throw new \Exception('Module '.$param['module'].' unknown for create function. ');
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $result[$idDoc] = [
                    'id' => -1,
                    'error' => $error,
                ];
            }
            // Modification du statut du flux
            $this->updateDocumentStatus($idDoc, $result[$idDoc], $param);
        }

        return $result;
    }

    // Ajout des filiale et du groupe en paramètre
    public function getFieldsParamUpd($type, $module)
    {
        try {
            $params = [];
            if ('source' == $type) {
                // Ajout du paramètre de l'organisation commerciale pour le partenaire
                if (in_array($module, ['BU_PARTNER'])) {
                    $params[] = [
                        'id' => 'SALES_ORG',
                        'name' => 'SALES_ORG',
                        'type' => TextType::class,
                        'label' => 'Sales Organization',
                        'required' => false,
                    ];

                    // Ajout du paramètre de groupe pour le partenaire
                    $params[] = [
                        'id' => 'BU_GROUP',
                        'name' => 'BU_GROUP',
                        'type' => TextType::class,
                        'label' => 'Partner group',
                        'required' => false,
                    ];
                }
                if (in_array($module, ['CRMD_ORDER'])) {
                    // Ajout du paramètre de type d'opération
                    $params[] = [
                        'id' => 'PROCESS_TYPE',
                        'name' => 'PROCESS_TYPE',
                        'type' => TextType::class,
                        'label' => 'Order type ', //Type d'opération commerciale
                        'required' => false,
                    ];
                    // Ajout du paramètre de type d'object
                    $params[] = [
                        'id' => 'OBJECT',
                        'name' => 'BU_GROUP',
                        'type' => TextType::class,
                        'label' => 'Object type',
                        'required' => false,
                    ];
                }
            }

            return $params;
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

            return [];
        }
    }

    protected function formatReadResponse($param, $response)
    {
        if ('BU_PARTNER' == $param['module']) {
            // Si une seule struture sélectionnée alors $response->EtFields->item n'est pas un tableau mais la structure directement.
            // On rajoute donc la dimension nécessaire à la suite du programme
            $addresses = $this->convertResponseTab($response->EtAddresses->item);

            // Boucle sur toutes les adresses de la réponse
            if (!empty($addresses)) {
                foreach ($addresses as $address) {
                    // Boucle sur toutes les stuctures de l'adresse
                    if (!empty($address)) {
                        foreach ($address as $structName => $structValue) {
                            // On ne traite pas la structure partner
                            if ('Partner' != $structName) {
                                // Formatage du nom de la structure + ajout du partner
                                $structName = 'Et'.$structName;
                                if (isset($structValue->item)) {
                                    $structures = $this->convertResponseTab($structValue->item);
                                    foreach ($structures as $struct) {
                                        $struct->Partner = $address->Partner;
                                        $response->$structName->item[] = $struct;
                                    }
                                } else {
                                    // Modification de la réponse pour ajouter les structure d'adresse
                                    $structValue->Partner = $address->Partner;

                                    $response->$structName->item[] = $structValue;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $response;
    }
}// class sapcrm

class sapcrm extends sapcrmcore
{
}
