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

use Symfony\Bridge\Monolog\Logger;
//use Psr\LoggerInterface;

class sapcrmcore extends saproot {

	// Permet de connaître la clé de filtrage principale sur les tables, la fonction partenire sur la table des partenaire par exemple
	// ces filtres correspondent aux sélections de l'utilisateur lors de la création de règle
	protected $keySubStructure = array('CRMD_ORDER' => array(
														'ET_PARTNER' => 'PARTNER_FCT',
														'ET_STATUS'  => 'USER_STAT_PROC',
														'ET_APPOINTMENT' => 'APPT_TYPE'
														),
										'BU_PARTNER' => array(
														'ET_BAPIADTEL' => 'STD_NO',
														'ET_BAPIADFAX' => 'STD_NO',
														'ET_BAPIADSMTP' => 'STD_NO',
														'ET_BAPIADURI' => 'STD_NO'
														),				
										);
	
	// Permet d'ajouter des filtres sur les tables, on ne prend que les partenaires principaux sur la table des partenaire par exemple
	protected $subStructureFilter = array(	'CRMD_ORDER' => array(
															'ET_PARTNER' => array('MAINPARTNER' => 'X')
														)			
										);			
	
	// Permet d'avoir les GUID pour chaque sous-structure et ainsi de savoirà quel partner/order... appartiennent les autre sstructures
	protected $guidName = array('CRMD_ORDER' => array(
														'ET_ORDERADM_H' => 'GUID',
														'ET_ACTIVITY_H' => 'GUID',
														'ET_STATUS' => 'GUID'
													),
								'BU_PARTNER' => array(
														'ET_BUT000' => 'PARTNER',
														'default' => 'PARTNER',
													),
										);	

	// Permet d'indiquer quel est l'id pour chaque module
	protected $idName = array(	
								'CRMD_ORDER' => array('ET_ORDERADM_H' => 'OBJECT_ID'),
								'BU_PARTNER' => array('ET_BUT000' => 'PARTNER')
							);												
				
	protected $required_fields =  array(
											'CRMD_ORDER' => array('ET_ORDERADM_H__CHANGED_AT','ET_ORDERADM_H__OBJECT_ID'),
											'BU_PARTNER' => array('ET_BUT000__CHDAT','ET_BUT000__CHTIM','ET_BUT000__PARTNER')
										);
	
	protected $relateFieldAllowed = array(	
											'CRMD_ORDER' => array(
																// 'ET_ORDERADM_H' => array('OBJECT_ID'=> array('label' =>  'Object ID','required_relationship' => false)),
																'ET_PARTNER' => array('PARTNER_NO'=> array('label' =>  'Partner number','required_relationship' => false))
																),
											// 'BU_PARTNER' => array(
																// 'ET_BUT000029PCFR' => array('PARTNER2'=> array('label' =>  'Agence','required_relationship' => false))
																// )					
										);	

	
										
	 public function login($paramConnexion) {
		$paramConnexion['wsdl'] = __DIR__.'/../Custom/Solutions/sapcrm/wsdl/'.$paramConnexion['wsdl'];			
		parent::login($paramConnexion);
	} // login($paramConnexion)*/	

	// Renvoie les modules disponibles du compte Salesforce connecté
	public function get_modules($type = 'source') {
		if ($type = 'source') {
			return array(

							'CRMD_ORDER' => 'Order',
							'BU_PARTNER' => 'Partner'
			);
		}
		else {
			return array(
							// 'CRMD_ORDER' => 'Order',
							'BU_PARTNER' => 'Partner'
			);
		}
	} // get_modules()

	public function get_module_fields($module, $type = 'source') {
		if ($type == 'target') {
			switch ($module) {
			    case 'BU_PARTNER':
					$this->moduleFields = array(
						'HEADER__CATEGORIE' => array('label' => 'CATEGORIE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__TYPE'),
						'HEADER__GROUP' => array('label' => 'GROUP', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BU_GROUP'),
						'HEADER__BUSINESSPARTNEREXTERN' => array('label' => 'BUSINESSPARTNEREXTERN', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BPEXT'),
						'CENTRALDATA__PARTNERTYPE' => array('label' => 'PARTNERTYPE', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0, 'readName' => 'ET_BUT000__BPKIND'),
						'CENTRALDATA__TITLE_KEY' => array('label' => 'TITLE_KEY', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0, 'readName' => 'ET_BUT000__TITLE'),
						'CENTRALDATAPERSON__FIRSTNAME' => array('label' => 'FIRSTNAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_FIRST'),
						'CENTRALDATAPERSON__LASTNAME' => array('label' => 'LASTNAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_LAST'),
						'CENTRALDATAPERSON__MIDDLENAME' => array('label' => 'MIDDLENAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAMEMIDDLE'),
						'CENTRALDATAPERSON__TITLE_ACA1' => array('label' => 'TITLE_ACA1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__TITLE_ACA1'),
						'CENTRALDATAPERSON__TITLE_ACA2' => array('label' => 'TITLE_ACA2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__TITLE_ACA2'),
						'CENTRALDATAPERSON__NICKNAME' => array('label' => 'NICKNAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NICKNAME'),
						'CENTRALDATAPERSON__BIRTHPLACE' => array('label' => 'BIRTHPLACE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BIRTHPL'),
						'CENTRALDATAPERSON__BIRTHDATE' => array('label' => 'BIRTHDATE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__BIRTHDT'),
						'CENTRALDATAPERSON__MARITALSTATUS' => array('label' => 'MARITALSTATUS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__MARST'), 
						'CENTRALDATAPERSON__EMPLOYER' => array('label' => 'EMPLOYER', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__EMPLO'), 
						'CENTRALDATAPERSON__NATIONALITY' => array('label' => 'NATIONALITY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NATIO'), 
						'CENTRALDATAPERSON__NATIONALITYISO' => array('label' => 'NATIONALITYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAMCOUNTRY'), 
						'CENTRALDATAORGANIZATION__NAME1' => array('label' => 'NAME1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG1'),
						'CENTRALDATAORGANIZATION__NAME2' => array('label' => 'NAME2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG2'),
						'CENTRALDATAORGANIZATION__NAME3' => array('label' => 'NAME3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG3'),
						'CENTRALDATAORGANIZATION__NAME4' => array('label' => 'NAME4', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_ORG4'),
						'CENTRALDATAORGANIZATION__INDUSTRYSECTOR' => array('label' => 'INDUSTRYSECTOR', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__IND_SECTOR'), 
						'CENTRALDATAGROUP__INAMEGROUP1' => array('label' => 'NAMEGROUP1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_GRP1'),
						'CENTRALDATAGROUP__INAMEGROUP2' => array('label' => 'NAMEGROUP2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__NAME_GRP2'),
						'CENTRALDATAGROUP__GROUPTYPE' => array('label' => 'GROUPTYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BUT000__PARTGRPTYP'),
						'ADDRESSDATA__STANDARDADDRESS' => array('label' => 'STANDARDADDRESS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STANDARDADDRESS'),
						'ADDRESSDATA__C_O_NAME' => array('label' => 'C_O_NAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__C_O_NAME'),
						'ADDRESSDATA__CITY' => array('label' => 'CITY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__CITY'),
						'ADDRESSDATA__POSTL_COD1' => array('label' => 'POSTL_COD1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__CITY'),
						'ADDRESSDATA__PO_BOX' => array('label' => 'PO_BOX', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__PO_BOX'),
						'ADDRESSDATA__PO_CTRYISO' => array('label' => 'PO_CTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__PO_CTRYISO'),
						'ADDRESSDATA__STREET' => array('label' => 'STREET', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STREET'),
						'ADDRESSDATA__HOUSE_NO' => array('label' => 'HOUSE_NO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__HOUSE_NO'),
						'ADDRESSDATA__STR_SUPPL1' => array('label' => 'STR_SUPPL1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STR_SUPPL1'),
						'ADDRESSDATA__STR_SUPPL2' => array('label' => 'STR_SUPPL2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STR_SUPPL2'),
						'ADDRESSDATA__STR_SUPPL3' => array('label' => 'STR_SUPPL3', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__STR_SUPPL3'),
						'ADDRESSDATA__BUILDING' => array('label' => 'BUILDING', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__BUILDING'),
						'ADDRESSDATA__FLOOR' => array('label' => 'FLOOR', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__FLOOR'),
						'ADDRESSDATA__ROOM_NO' => array('label' => 'ROOM_NO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__ROOM_NO'),
						'ADDRESSDATA__COUNTRY' => array('label' => 'COUNTRY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__COUNTRY'),
						'ADDRESSDATA__COUNTRYISO' => array('label' => 'COUNTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__COUNTRYISO'),
						'ADDRESSDATA__REGION' => array('label' => 'REGION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ADDRESSDATA__REGION'),
						'TELEFONDATA__TELEPHONE' => array('label' => 'TELEPHONE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADTEL__X__TELEPHONE'),
						'TELEFONDATA__COUNTRYISO' => array('label' => 'COUNTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADTEL__X__COUNTRYISO'),
						'FAXDATA__FAX' => array('label' => 'FAX', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADFAX__X__FAX'),
						'FAXDATA__COUNTRYISO' => array('label' => 'COUNTRYISO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADFAX__X__COUNTRYISO'),
						'E_MAILDATA__E_MAIL' => array('label' => 'E_MAIL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADSMTP__X__E_MAIL'),
						'URIADDRESSDATA__URI' => array('label' => 'URI', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADURI__X__URI'),
						'URIADDRESSDATA__URI_TYPE' => array('label' => 'URI_TYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_BAPIADURI__X__URI_TYPE')
					);
					break;
				case 'CRMD_ORDER':	
					$this->moduleFields = array(
						'ORDERADM_H__PROCESS_TYPE' => array('label' => 'PROCESS_TYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'readName' => 'ET_ORDERADM_H__PROCESS_TYPE'),
						'ORDERADM_H__POSTING_DATE' => array('label' => 'POSTING_DATE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ORDERADM_H__POSTING_DATE'),
						'ORDERADM_H__DESCRIPTION' => array('label' => 'DESCRIPTION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ORDERADM_H__DESCRIPTION'),
						'STATUS__ZSPIR_R1__STATUS' => array('label' => 'STATUS', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_STATUS__ZSPIR_R1__STATUS'),
						'STATUS__ZSPIR_R1__USER_STAT_PROC' => array('label' => 'USER_STAT_PROC', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_STATUS__ZSPIR_R1__USER_STAT_PROC'),
						'ACTIVITY_H__NAME' => array('label' => 'NAME', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__NAME'),
						'ACTIVITY_H__CATEGORY' => array('label' => 'CATEGORY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__CATEGORY'),
						'ACTIVITY_H__PRIORITY' => array('label' => 'PRIORITY', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__DESCRIPTION'),
						'ACTIVITY_H__OBJECTIVE' => array('label' => 'OBJECTIVE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__OBJECTIVE'),
						'ACTIVITY_H__DIRECTION' => array('label' => 'DIRECTION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__DIRECTION'),
						'ACTIVITY_H__PRIVATE_FLAG' => array('label' => 'PRIVATE_FLAG', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__PRIVATE_FLAG'),
						'ACTIVITY_H__COMPLETION' => array('label' => 'COMPLETION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__COMPLETION'),
						'ACTIVITY_H__ACT_LOCATION' => array('label' => 'ACT_LOCATION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_ACTIVITY_H__ACT_LOCATION'),
						'APPOINTMENT__ORDERPLANNED__APPT_TYPE' => array('label' => 'ORDERPLANNED__APPT_TYPE', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__APPT_TYPE'),
						'APPOINTMENT__ORDERPLANNED__TIMESTAMP_FROM' => array('label' => 'ORDERPLANNED__TIMESTAMP_FROM', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMESTAMP_FROM'),
						'APPOINTMENT__ORDERPLANNED__TIMEZONE_FROM' => array('label' => 'ORDERPLANNED__TIMEZONE_FROM', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMESTAMP_TO'),
						'APPOINTMENT__ORDERPLANNED__TIMESTAMP_TO' => array('label' => 'ORDERPLANNED__TIMESTAMP_TO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMESTAMP_TO'),
						'APPOINTMENT__ORDERPLANNED__TIMEZONE_TO' => array('label' => 'ORDERPLANNED__TIMEZONE_TO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__TIMEZONE_TO'),
						'APPOINTMENT__ORDERPLANNED__DOMINANT' => array('label' => 'ORDERPLANNED__DOMINANT', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__DOMINANT'),
						'APPOINTMENT__ORDERPLANNED__DURATION' => array('label' => 'ORDERPLANNED__DURATION', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'readName' => 'ET_APPOINTMENT__ORDERPLANNED__DURATION'),
					);
					$this->fieldsRelate = array(
						'PARTNER__00000009__PARTNER_NO' => array('label' => '00000009__PARTNER_NO', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'required_relationship' => 0, 'readName' => 'ET_PARTNER__00000009__PARTNER_NO'),
					);
					break; 
			}
			return $this->moduleFields;

		}
		else {
			return parent::get_module_fields($module, $type);
		}
	}
	
	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {	
		if ($param['module'] == 'CRMD_ORDER') {
			// return $this->readOrder($param,true);
			// Si 1 id est demandé alors on récupère l'opération correspondante
			if(!empty($param['query']['id'])) {
				$parameters = array(
					'IvDateRef' => '',
					'IvLimit' => '',
					'IvObjectId' => $param['query']['id'],
					'IvObjectType' => '',
					'IvProcessType' => '',
					'IvTypeDate' => ''
				);
				// Permet de modifier les nom des champs pour le read_last.  Dans SAP les champs en lecture et en écriture ne sont pas toujours identiques pour le même module, les structures peuvent être différentes.
				$convertFieldReadLast = $this->convertFieldReadLast($param,'','1');	
				if ($convertFieldReadLast['done'] === -1) {
					return $convertFieldReadLast;
				}

				// Copie des param
				$param2 = $param;
				$param2['fields'] = $convertFieldReadLast['fields'];	
				// Récupération du partner 
				$readLast = $this->readMultiStructure($param2,'ZmydSearchOrders',$parameters,true);			

				// On remets les champs comme attendu par Myddleware
				$convertFieldReadLastValues = $this->convertFieldReadLast($param,$readLast['values'],'2');
				if ($convertFieldReadLastValues['done'] === -1) {
					return $convertFieldReadLastValues;
				}
				$readLast['values'] = $convertFieldReadLastValues['fields'];
				return $readLast;
			}
			// Sinon envoie la date 99991231000000 à SAP pour qu'il nous renvoie le dernier élément
			else {
				$parameters = array(
					'IvDateRef' => '99991231000000',
					'IvLimit' => '',
					'IvObjectId' => '',
					'IvObjectType' => '',
					'IvProcessType' => '',
					'IvTypeDate' => 'C'
				);
				return $this->readMultiStructure($param,'ZmydSearchOrders',$parameters,true);
			}
		}
		if ($param['module'] == 'BU_PARTNER') {				
			// Si 1 id est demandé alors on récupère l'opération correspondante (ici pour la récupération d'historique de données)
			if(!empty($param['query']['id'])) {
				$parameters = array(
					'IvDateRef' => '',
					'IvLimit' => '',
					'IvParner' => $param['query']['id'],
					'IvTypeDate' => ''
				);

				// Permet de modifier les nom des champs pour le read_last.  Dans SAP les champs en lecture et en écriture ne sont pas toujours identiques pour le même module, les structures peuvent être différentes.
				$convertFieldReadLast = $this->convertFieldReadLast($param,'','1');	
				if ($convertFieldReadLast['done'] === -1) {

					return $convertFieldReadLast;
				}
				// Copie des param
				$param2 = $param;
				$param2['fields'] = $convertFieldReadLast['fields'];								
				// Récupération du partner 
				$readLast = $this->readMultiStructure($param2,'ZmydSearchBp',$parameters,true);					
				// On remets les champs comme attendu par Myddleware
				$convertFieldReadLastValues = $this->convertFieldReadLast($param,$readLast['values'],'2');
				if ($convertFieldReadLastValues['done'] === -1) {
					return $convertFieldReadLastValues;
				}
				$readLast['values'] = $convertFieldReadLastValues['fields'];
				return $readLast;
			}
			// Sinon envoie la date 99991231000000 à SAP pour qu'il nous renvoie le dernier élément (ici utile pour le test dans Myddleware)
			else {
				$parameters = array(
					'IvDateRef' => '99991231000000',
					'IvLimit' => '',
					'IvParner' => '',
					'IvTypeDate' => 'C'
				);
				return $this->readMultiStructure($param,'ZmydSearchBp',$parameters,true);
			}
		}
	} // read_last($param)	

	
	// Permet de modifier les nom des champs pour le read_last 
	// Dans SAP les champs en lecture et en écriture ne sont pas toujours identiques pour le même module, les structures peuvent être différentes
	protected function convertFieldReadLast($param,$values,$mode) {	
		try {			
			$this->get_module_fields($param['module'],'target');
			if (!empty($this->moduleFields)) {
				if (!empty($param['fields'])) {
					$convertFields = array();			
					foreach($param['fields'] as $field) {	
						// Si on a pas de données alors on a à mettre les noms de champ attendus par le SEARCH_BP de SAP
						if ($mode == '1'){				
							if (!empty($this->moduleFields[$field]['readName'])) {
								$convertFields[] = $this->moduleFields[$field]['readName'];
							}
							elseif (!empty($this->fieldsRelate[$field]['readName'])) {
								$convertFields[] = $this->fieldsRelate[$field]['readName'];
							}
							else {
								throw new \Exception( 'The field '.$field.' has no readName. Failed to read data in SAP CRM. ');
							}
						}

						// Sinon c'est que l'on a eu le retour de SAP et que l'on doit remettre les nom d'orgine des champs
						elseif ($mode == '2'){ 				
							if (
									!empty($this->moduleFields[$field]['readName'])
								&&	!empty($values[$this->moduleFields[$field]['readName']])
							) {									
								$convertFields[$field] = $values[$this->moduleFields[$field]['readName']];
							}
							elseif (
									!empty($this->fieldsRelate[$field]['readName'])
								&&	!empty($values[$this->fieldsRelate[$field]['readName']])
							) {							
								$convertFields[$field] = $values[$this->fieldsRelate[$field]['readName']];
							}							

						}

					}
					// Dans le cas où on transforme le retour de SAP on ajoute l'id dans le tableau corrigé
					if (!empty($values)){
						$convertFields['id'] = $values['id'];
					}
				}

			}
			return array('done' => 1, 'fields' => $convertFields);
		}
		catch (\Exception $e) {
			$error = 'Failed to read the record from sapcrm : '.$e->getMessage().' '.__CLASS__.' Line : '.$e->getLine().'. ';;
			return array('done' => -1, 'error' => $error);
		} 

	}	
	
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function read($param) {
		// Initialisation de la limit
		if (empty($param['limit'])) {
			$param['limit'] = $this->limit;
		}
		// Conversion de la date ref au format SAP
		if(!empty($param['date_ref'])) {
			$param['date_ref_format'] = $this->dateTimeFromMyddleware($param['date_ref']);
		}
		
		if ($param['module'] == 'CRMD_ORDER') {
			$parameters = array(
						'IvDateRef' => $param['date_ref_format'],
						'IvLimit' => $param['limit'],
						'IvObjectId' => '',
						'IvObjectType' => '',
						'IvProcessType' => '',
						'IvTypeDate' => ($param['ruleParams']['mode'] == 'C' ? 'C' : 'U')
					);
			// return $this->readOrder($param,false);
			return $this->readMultiStructure($param,'ZmydSearchOrders',$parameters,false);
		}
		if ($param['module'] == 'BU_PARTNER') {
			$parameters = array(
						'IvDateRef' => $param['date_ref_format'],
						'IvLimit' => $param['limit'],
						// 'IvLimit' => 100,
						'IvParner' => '',
						'IvBuGroup' => (empty($param['ruleParams']['BU_GROUP']) ? '' : $param['ruleParams']['BU_GROUP']),
						'IvSalesOrg' => (empty($param['ruleParams']['SALES_ORG']) ? '' : $param['ruleParams']['SALES_ORG']),
						'IvTypeDate' => ($param['ruleParams']['mode'] == 'C' ? 'C' : 'U')
					);
			return $this->readMultiStructure($param,'ZmydSearchBp',$parameters,false);
		}
	} // read($param)
	
	
	// Permet de créer des données
	public function create($param) {	

		// Transformation du tableau d'entrée pour être compatible webservice SAP CRM
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$dataSugar = array();
				foreach ($data as $key => $value) {
					$tabValue = explode ('__',$key);			
					if (
							empty($tabValue[0])
						 ||	empty($tabValue[1])
					) {
						$result[$idDoc] = array(
											'id' => $idDoc,
											'error' => 'The field '.$key.' is not compatible. No caractere __ inside the name of this field.'
									);

						break;			
					}
					// On mets dans ZzmydKey tous les éléments sauf la structure (le +2 permet de e pas mettre les 2 _)
					$dataSapCrm[] = array('ZzmydKey' => substr($key,strlen($tabValue[0])+2), 'ZzmydValue' => $value, 'ZzmydType' => '', 'ZzmydLabel' => $tabValue[0]);
				}
				$parameters = array(
						'ItData' => $dataSapCrm
					);					
				if ($param['module'] == 'BU_PARTNER') {
					$response = $this->client->ZmydCreateBp($parameters);
					if ($response->EvTypeMessage == 'E') {
						throw new \Exception( 'Failed to create partner : '.utf8_encode($response->EvMessage).'.');
					}					
					if (!empty($response->EvPartner)) {
						$result[$idDoc] = array(
												'id' => $response->EvPartner,
												'error' => false
										);
					}
					else  {
						$result[$idDoc] = array(
												'id' => -1,
												'error' => '01'
										);
					}					
				}
				elseif ($param['module'] == 'CRMD_ORDER') {
					$response = $this->client->ZmydCreateOrder($parameters);
					if ($response->EvTypeMessage == 'E') {
						throw new \Exception( 'Failed to create order : '.utf8_encode($response->EvMessage).'.');
					}
					if (!empty($response->EvObjectId)) {
						$result[$idDoc] = array(
												'id' => $response->EvObjectId,
												'error' => false
										);
					}
					else  {
						$result[$idDoc] = array(
												'id' => -1,
												'error' => '01'
										);
					}			
				}
				else {
					throw new \Exception( 'Module '.$param['module'].' unknown for create function. ');

				}	
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => -1,
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}		
		return $result;
	}
		
		
		// Permet de créer des données
	public function update($param) {		
		// Transformation du tableau d'entrée pour être compatible webservice SAP CRM
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before update
				$data = $this->checkDataBeforeUpdate($param, $data);
				$dataSugar = array();
				// Récupération des modifications seulement
				$diff = array_diff ($data, $param['dataHistory'][$idDoc]);		
				foreach ($diff as $key => $value) {
					$tabValue = explode ('__',$key);			
					if (
							empty($tabValue[0])
						 ||	empty($tabValue[1])
					) {
						$result[$idDoc] = array(
											'id' => $idDoc,
											'error' => 'The field '.$key.' is not compatible. No caractere __ inside the name of this field.'
									);
						break;			
					}
					// On mets dans ZzmydKey tous les éléments sauf la structure (le +2 permet de e pas mettre les 2 _)
					$dataSapCrm[] = array('ZzmydKey' => substr($key,strlen($tabValue[0])+2), 'ZzmydValue' => $value, 'ZzmydType' => '', 'ZzmydLabel' => $tabValue[0]);
				}
				
				if ($param['module'] == 'BU_PARTNER') {
					$parameters = array(
						'ItData' => $dataSapCrm,
						'EvPartner' => $data['target_id']
					);
					$response = $this->client->ZmydUpdateBp($parameters);
					if ($response->EvTypeMessage == 'E') {
						throw new \Exception( 'Failed to update partner : '.utf8_encode($response->EvMessage).'.');
					}					
					if (!empty($response->EvPartner)) {
						$result[$idDoc] = array(
												'id' => $response->EvPartner,
												'error' => false
										);
					}
					else  {
						$result[$idDoc] = array(
												'id' => -1,
												'error' => '01'
										);
					}					
				}
				elseif ($param['module'] == 'CRMD_ORDER') {
					$parameters = array(
						'ItData' => $dataSapCrm,
						'EvObjectId' => $data['target_id']
					);
					$response = $this->client->ZmydUpdateOrder($parameters);
					if ($response->EvTypeMessage == 'E') {
						throw new \Exception( 'Failed to update order : '.utf8_encode($response->EvMessage).'.');
					}
					if (!empty($response->EvObjectId)) {
						$result[$idDoc] = array(
												'id' => $response->EvObjectId,
												'error' => false
										);
					}
					else  {
						$result[$idDoc] = array(
												'id' => -1,
												'error' => '01'
										);
					}			
				}
				else {
					throw new \Exception( 'Module '.$param['module'].' unknown for create function. ');
				}	
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => -1,
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}	
		return $result;
	}
			
	
	// Ajout des filiale et du groupe en paramètre
	public function getFieldsParamUpd($type,$module, $myddlewareSession) {	
		try {
			$params = array();
			if ($type == 'source'){
				// Ajout du paramètre de l'organisation commerciale pour le partenaire
				if (in_array($module,array('BU_PARTNER'))) {
					$params[] = array(
								'id' => 'SALES_ORG',
								'name' => 'SALES_ORG',
								'type' => 'text',
								'label' => 'Sales Organization',
								'required'	=> false
							);
			
					// Ajout du paramètre de groupe pour le partenaire
					$params[] = array(
								'id' => 'BU_GROUP',
								'name' => 'BU_GROUP',
								'type' => 'text',
								'label' => 'Partner group',
								'required'	=> false
							);					
				}
				if (in_array($module,array('CRMD_ORDER'))) {
					// Ajout du paramètre de type d'opération
					$params[] = array(
								'id' => 'PROCESS_TYPE',
								'name' => 'PROCESS_TYPE',
								'type' => 'text',
								'label' => 'Order type ', //Type d'opération commerciale
								'required'	=> false
							);			
					// Ajout du paramètre de type d'object
					$params[] = array(
								'id' => 'OBJECT',
								'name' => 'BU_GROUP',
								'type' => 'text',
								'label' => 'Object type',
								'required'	=> false
							);					
				}
			}
			return $params;
		}
		catch (\Exception $e){
			$this->logger->error('Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )');	
			return array();
		}
	}

	protected function formatReadResponse($param,$response) {
		if ($param['module'] == 'BU_PARTNER') {
			// Si une seule struture sélectionnée alors $response->EtFields->item n'est pas un tableau mais la structure directement.
			// On rajoute donc la dimension nécessaire à la suite du programme
			$addresses = $this->convertResponseTab($response->EtAddresses->item);
			
			// Boucle sur toutes les adresses de la réponse
			if (!empty($addresses)) {
				foreach ($addresses as $address){
					// Boucle sur toutes les stuctures de l'adresse
					if (!empty($address)) {
						foreach ($address as $structName => $structValue){
							// On ne traite pas la structure partner
							if ($structName != 'Partner') {
								// Formatage du nom de la structure + ajout du partner
								$structName = 'Et'.$structName;
								if (isset($structValue->item)) {		
									$structures = $this->convertResponseTab($structValue->item);
									foreach ($structures as $struct) {
										$struct->Partner = $address->Partner;
										$response->$structName->item[] = $struct;

									}
								}

								else {
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

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/sapcrm/sapcrm.php';
if(file_exists($file)){
	include($file);
}
else {
	//Sinon on met la classe suivante
	class sapcrm extends sapcrmcore {
		
	}
}