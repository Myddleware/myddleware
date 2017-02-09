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

class dolistcore extends solution {
	
	private $token;
	private $accountId;
		
	// Chemins d'upload
	protected $dolistFtpHost = "ftp.dolist.net";
	protected $dolistFtpUploadDir = "/upload/contact";	
	protected $noInfiniteWhile = 500;	
			
	// Connexion à Dolist
    public function login($paramConnexion) {
		parent::login($paramConnexion);
		try{ // Gestion d'erreur Symfony
			try{ // Gestion d'erreur SOAP
				ini_set("soap.wsdl_cache_enabled", "0");
				ini_set("default_socket_timeout", 480);  
				
				// Url du contrat wsdl
				$proxywsdl = "http://api.dolist.net/v2/AuthenticationService.svc?wsdl";
				$location = "http://api.dolist.net/v2/AuthenticationService.svc/soap1.1";
				
				// Génération du proxy
				$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));            
			
				// Renseigner la clé d'authentification avec l'identifiant client
				$authenticationInfos	= array('AuthenticationKey' => $this->paramConnexion['apikey'],'AccountID' => $this->paramConnexion['accountid']);
				$authenticationRequest	= array('authenticationRequest' => $authenticationInfos);
				
				// Demande du jeton d'authentification
				$result = $client->GetAuthenticationToken($authenticationRequest);
	
				// Instanciation des variables de classes
				$this->connexion_valide = true;
				$this->token = $result->GetAuthenticationTokenResult->Key;
				$this->accountId = $this->paramConnexion['accountid'];
				$this->login = $this->paramConnexion['login'];
				$this->password = $this->paramConnexion['password'];
				
			} // Gestion d'erreur SOAP
			catch(\SoapFault $fault) {
				$Detail = $fault->detail;
				throw new \Exception($Detail->ServiceException->Description);
			}
		} // Gestion d'erreur Symfony
		catch(\Exception $e){
			$error = 'Failed to login to Dolist : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
	
	
	// Liste des paramètres de connexion
	public function getFieldsLogin() {	
        return array(
                    array(
                            'name' => 'login',
                            'type' => 'text',
                            'label' => 'solution.fields.login'
                        ),
                    array(
                            'name' => 'password',
                            'type' => 'password',
                            'label' => 'solution.fields.password'
                        ),
                   array(
                            'name' => 'accountid',
                            'type' => 'text',
                            'label' => 'solution.fields.accountid'
                        ),
                   array(
                            'name' => 'apikey',
                            'type' => 'text',
                            'label' => 'solution.fields.apikey'
                        )
        );
	} // getFieldsLogin()
	
	// Renvoie les modules disponibles
	public function get_modules($type = 'source') {
		try{
			if($type == 'source'){
				return array("contact" => "Contacts", "campagne" => "Campagnes", "statopen" => "Statistiques Ouvertures", "statclick" => "Statistiques Clics", "statdelivery" => "Statistiques Aboutis", "stathardbounce" => "Statistiques Non Aboutis", "statunsub" => "Statistiques Désabonnés");
			} else {
				return array("CampagneCible" => "Campagnes", "contactCible" => "Contacts", "StaticSegmentHeader" => "Entête Segment Statique", "StaticSegmentBody" => "Corps Segment Statique");
			}	
		} catch (\Exception $e) {
			$error = $e->getMessage();
			return $error;			
		}
	} // get_modules()	
	
	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		// Initiatlisation du paramètre limit
		if (empty($param['limit'])) {
            $param['limit'] = 100;
        }
		try{
			try{
				switch ($module) {
					case 'contact':
						$this->get_contact_fields();
						break;
					case 'campagne':
						$this->get_campagne_fields();
						break;
					case 'statopen':
						$this->get_statopen_fields();
						break;
					case 'statclick':
						$this->get_statclick_fields();
						break;
					case 'statdelivery':
						$this->get_statdelivery_fields();
						break;
					case 'stathardbounce':
						$this->get_stathardbounce_fields();
						break;
					case 'statunsub':
						$this->get_statunsub_fields();
						break;
					case 'StaticSegmentHeader':
						$this->get_StaticSegmentHeader_fields();
						break;
					case 'StaticSegmentBody':
						$this->get_StaticSegmentBody_fields();
						break;
					case 'contactCible':
						$this->get_contact_cible_fields();
						break;
					case 'CampagneCible':
						$this->get_campagne_cible_fields();
						break;
					default:
						throw new \Exception("Error Retreiving Module");
						break;
				}
				// Ajout des champ relate au mapping des champs 
				if (!empty($this->fieldsRelate)) {
					$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
				}
				return $this->moduleFields;
			}
			catch(\SoapFault $fault) {
				$Detail = $fault->detail;
				throw new \Exception($Detail->ServiceException->Description);
			}
		}
		catch (\Exception $e){
			$error = $e->getMessage();
			return $error;
		}
	} // get_module_fields($module)	
	
	// Récupère les champs du module contact
	public function get_contact_fields(){
		$proxywsdl = "http://api.dolist.net/V2/ContactManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/ContactManagementService.svc/soap1.1";
						
		$optionsRequest = array(
			'Offset' => 0, //Optionnel: L'indice du 1er contact retourné. 
			'AllFields' => true, //Indique si on doit retourner tous les champs
			'Interest' => true, //Indique si les interets déclarés sont retourné par la requete
			'LastModifiedOnly' => false, //Indique si la requete doit retourner uniquement les derniers contacts modifiés
		);
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$requestClient = array(
			'token'=> $token,
			'request'=> $optionsRequest
		);
		
		$resultClient = $client->GetContact($requestClient);
		
		if (!is_null($resultClient->GetContactResult) and $resultClient->GetContactResult != '')
		{
			$contacts = $resultClient->GetContactResult->ContactList;
			$contacts = json_decode(json_encode((array)$contacts), true);
			
			if(empty($contacts['ContactData'][0])) throw new \Exception("No data found");
			
			foreach ($contacts['ContactData'][0] as $field => $value) {
				if($field == 'CustomFields') continue;
				if($field == "Interests") continue;
		    	if($field == 'MemberId') {
		    		$this->fieldsRelate[$field] = array(
							'label' => $field,
							'type' => 'text',
							'type_bdd' => 'varchar(255)',
							'required' => false,
							'required_relationship' => 0
					);
				}
				$this->moduleFields[$field] = array(
						'label' => $field,
						'type' => 'text',
						'type_bdd' => 'varchar(255)',
						'required' => false
				);
			}
			$proxywsdl = "http://api.dolist.net/CustomFieldManagementService.svc?wsdl";
			$location = "http://api.dolist.net/CustomFieldManagementService.svc/soap1.1";
			
			// Génération du proxy
			$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
			 
			// Création du jeton
			$token = array(
				'AccountID' => $this->accountId,
				'Key' => $this->token
			);
						
			// Création de la requête
			$GetFieldListRequest = array(
				'token' => $token,
				'request' => array()
			);
			
			// Récupération de tous les segments
			$resultClient = $client->GetFieldList($GetFieldListRequest);

			if(!empty($resultClient->GetFieldListResult->FieldList)) {
				$customFields = $resultClient->GetFieldListResult->FieldList->Field;
				$customFields = json_decode(json_encode((array)$customFields), true);
				foreach ($customFields as $customField) {
					$this->moduleFields[$customField['Name']] = array(
							'label' => $customField['Title'],
							'type' => 'text',
							'type_bdd' => 'varchar(255)',
							'required' => false
					);					
				}
			}
		}
		else
		{
			throw new \Exception("No data found");
		}		
	} // get_contact_fields()	

	// TO CHANGE
	public function get_contact_cible_fields(){
		$proxywsdl = "http://api.dolist.net/CustomFieldManagementService.svc?wsdl";
		$location = "http://api.dolist.net/CustomFieldManagementService.svc/soap1.1";
		
		// Génération du proxy
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		 
		// Création du jeton
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
					
		// Création de la requête
		$GetFieldListRequest = array(
			'token' => $token,
			'request' => array()
		);
		
		// Récupération de tous les segments
		$resultClient = $client->GetFieldList($GetFieldListRequest);

		if(!empty($resultClient->GetFieldListResult->FieldList)) {
			$customFields = $resultClient->GetFieldListResult->FieldList->Field;
			$customFields = json_decode(json_encode((array)$customFields), true);
			foreach ($customFields as $customField) {
				if($customField['Name'] == 'email') {
					$this->moduleFields[$customField['Name']] = array(
							'label' => $customField['Title'],
							'type' => 'text',
							'type_bdd' => 'varchar(255)',
							'required' => true
					);
					continue;
				}
				$this->moduleFields[$customField['Name']] = array(
						'label' => $customField['Title'],
						'type' => 'text',
						'type_bdd' => 'varchar(255)',
						'required' => false
				);					
			}
		}
	} // get_contact_cible_fields()		

	// Récupère les champs du module CampagneCible
	public function get_campagne_cible_fields(){
		$fields = array(
			array('Name' => 'Culture', 'Label' => "Culture de la campagne", 'Required' => true),
			array('Name' => 'FormatLinkTechnical', 'Label' => "Type d'affichage pour les liens", 'Required' => false),
			array('Name' => 'FromAddressPrefix', 'Label' => "Préfixe de l'adresse de l'expéditeur", 'Required' => true),
			array('Name' => 'FromName', 'Label' => "Nom de l'expéditeur", 'Required' => true),
			array('Name' => 'Message', 'Label' => "Identifiant du message de la campagne", 'Required' => true),
			array('Name' => 'ReplyAddress', 'Label' => "Adresse de réponse", 'Required' => false),
			array('Name' => 'ReplyName', 'Label' => "Nom de réponse", 'Required' => false),
			array('Name' => 'Subject', 'Label' => "Sujet", 'Required' => true),
			array('Name' => 'TrackingDomain', 'Label' => "Tracking Domain", 'Required' => true),
			array('Name' => 'UnsubscribeFormId', 'Label' => "Identifiant du lien de désabonnement", 'Required' => false),
			array('Name' => 'VersionOnline', 'Label' => "Booléen lien version en ligne", 'Required' => false),
		);

		foreach ($fields as $field) {
			$this->moduleFields[$field['Name']] = array(
					'label' => $field['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => $field['Required']
			);
		}
	} // get_campagne_cible_fields()	
		
	// Récupère les champs du module statopen
	public function get_statopen_fields(){
		$fields = array(
			array('Name' => 'EventDate', 'Label' => "Date de l’ouverture"),
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'Email', 'Label' => "Mail du contact"),
			array('Name' => 'EventTotal', 'Label' => "Nb d’ouvertures du contact sur la campagne"),
			array('Name' => 'LastClickDate', 'Label' => "Date de dernier clic du contact"),
			array('Name' => 'LastOpenDate', 'Label' => "Date de dernière ouverture du contact"),
			array('Name' => 'EventDistinct', 'Label' => "Nb d’ouvertures de la campagne"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'Optout', 'Label' => "Identifiant de l’etat du contact email"),
			array('Name' => 'SalutationID', 'Label' => "Identifiant de la civilité"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'CountryID', 'Label' => "Identifiant du pays"),
			array('Name' => 'JoinDate', 'Label' => "Date d’insertion du contact en base"),
			array('Name' => 'LeaveDate', 'Label' => "Date de passage du contact en inactif"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact"),
			array('Name' => 'ProfileUpdateDate', 'Label' => "Date de mise à jour du profil du contact"),
			array('Name' => 'LastOpenDate', 'Label' => "Date de dernière ouverture du contact"),
			array('Name' => 'Sent', 'Label' => "Nombre de message envoyés sur ce contact depuis son inscription"),
		);

		$fieldsRelate = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact")
		);

		foreach ($fields as $field) {
			$this->moduleFields[$field['Name']] = array(
					'label' => $field['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false
			);
		}

		foreach ($fieldsRelate as $relate) {
			$this->fieldsRelate[$relate['Name']] = array(
					'label' => $relate['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false,
					'required_relationship' => 0
			);
		}
		
	} // get_statopen_fields()	
	
	// Récupère les champs du module statclick
	public function get_statclick_fields(){
		$fields = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'Email', 'Label' => "Mail du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'Optout', 'Label' => "Identifiant de l’etat du contact email"),
			array('Name' => 'SalutationID', 'Label' => "Identifiant de la civilité"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'CountryID', 'Label' => "Identifiant du pays"),
			array('Name' => 'JoinDate', 'Label' => "Date d’insertion du contact en base"),
			array('Name' => 'LeaveDate', 'Label' => "Date de passage du contact en inactif"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact"),
			array('Name' => 'ProfileUpdateDate', 'Label' => "Date de mise à jour du profil du contact"),
			array('Name' => 'Sent', 'Label' => "Nombre de message envoyés sur ce contact depuis son inscription"),
			array('Name' => 'LastClickDate', 'Label' => "Date de dernier clic du contact"),
			array('Name' => 'LastOpenDate', 'Label' => "Date de dernière ouverture du contact"),
			
			array('Name' => 'EventDistinct', 'Label' => "Nb de clic de la campagne"),
			array('Name' => 'EventTotal', 'Label' => "Nb de clic du contact sur la campagne"),
			array('Name' => 'EventDate', 'Label' => "Date du clic"),
			array('Name' => 'LinkID', 'Label' => "Identifiant du lien cliqué"),
			array('Name' => 'URL', 'Label' => "URL du lien cliqué"),
			array('Name' => 'ThemeID', 'Label' => "Identifiant du thème associé au lien"),
			array('Name' => 'ThemeName', 'Label' => "Description du thème"),
		);
		
		$fieldsRelate = array(
			array('Name' => 'ThemeID', 'Label' => "Identifiant du thème associé au lien"),
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact")
		);

		foreach ($fields as $field) {
			$this->moduleFields[$field['Name']] = array(
					'label' => $field['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false
			);
		}

		foreach ($fieldsRelate as $relate) {
			$this->fieldsRelate[$relate['Name']] = array(
					'label' => $relate['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false,
					'required_relationship' => 0
			);
		}
		
	} // get_statclick_fields()
	
	// Récupère les champs du module statdelivery
	public function get_statdelivery_fields(){
		$fields = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'Email', 'Label' => "Mail du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'Optout', 'Label' => "Identifiant de l’etat du contact email"),
			array('Name' => 'SalutationID', 'Label' => "Identifiant de la civilité"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'CountryID', 'Label' => "Identifiant du pays"),
			array('Name' => 'JoinDate', 'Label' => "Date d’insertion du contact en base"),
			array('Name' => 'LeaveDate', 'Label' => "Date de passage du contact en inactif"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact"),
			array('Name' => 'ProfileUpdateDate', 'Label' => "Date de mise à jour du profil du contact"),
			array('Name' => 'Sent', 'Label' => "Nombre de message envoyés sur ce contact depuis son inscription"),
			array('Name' => 'LastClickDate', 'Label' => "Date de dernier clic du contact"),
			array('Name' => 'LastOpenDate', 'Label' => "Date de dernière ouverture du contact"),
			
			array('Name' => 'EventDistinct', 'Label' => "Nb d’aboutis de la campagne"),
			array('Name' => 'EventTotal', 'Label' => "Nb d’aboutis du contact sur la campagne"),
			array('Name' => 'EventDate', 'Label' => "Date de réception du statut abouti"),
		);
		
		$fieldsRelate = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact")
		);

		foreach ($fields as $field) {
			$this->moduleFields[$field['Name']] = array(
					'label' => $field['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false
			);
		}

		foreach ($fieldsRelate as $relate) {
			$this->fieldsRelate[$relate['Name']] = array(
					'label' => $relate['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false,
					'required_relationship' => 0
			);
		}
		
	} // get_statdelivery_fields()
	
	// Récupère les champs du module stathardbounce
	public function get_stathardbounce_fields(){
		$fields = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'Email', 'Label' => "Mail du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'Optout', 'Label' => "Identifiant de l’etat du contact email"),
			array('Name' => 'SalutationID', 'Label' => "Identifiant de la civilité"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'CountryID', 'Label' => "Identifiant du pays"),
			array('Name' => 'JoinDate', 'Label' => "Date d’insertion du contact en base"),
			array('Name' => 'LeaveDate', 'Label' => "Date de passage du contact en inactif"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact"),
			array('Name' => 'ProfileUpdateDate', 'Label' => "Date de mise à jour du profil du contact"),
			array('Name' => 'Sent', 'Label' => "Nombre de message envoyés sur ce contact depuis son inscription"),
			array('Name' => 'LastClickDate', 'Label' => "Date de dernier clic du contact"),
			array('Name' => 'LastOpenDate', 'Label' => "Date de dernière ouverture du contact"),
			
			array('Name' => 'EventDistinct', 'Label' => "Nb de hardbounces de la campagne"),
			array('Name' => 'EventTotal', 'Label' => "Nb de harbounces du contact sur la campagne"),
			array('Name' => 'EventDate', 'Label' => "Date de reception du status hardbounce"),
		);
		
		$fieldsRelate = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact")
		);

		foreach ($fields as $field) {
			$this->moduleFields[$field['Name']] = array(
					'label' => $field['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false
			);
		}

		foreach ($fieldsRelate as $relate) {
			$this->fieldsRelate[$relate['Name']] = array(
					'label' => $relate['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false,
					'required_relationship' => 0
			);
		}
		
	} // get_stathardbounce_fields()
	
	// Récupère les champs du module statstatunsub
	public function get_statunsub_fields(){
		$fields = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'Email', 'Label' => "Mail du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'Optout', 'Label' => "Identifiant de l’etat du contact email"),
			array('Name' => 'SalutationID', 'Label' => "Identifiant de la civilité"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'CountryID', 'Label' => "Identifiant du pays"),
			array('Name' => 'JoinDate', 'Label' => "Date d’insertion du contact en base"),
			array('Name' => 'LeaveDate', 'Label' => "Date de passage du contact en inactif"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact"),
			array('Name' => 'ProfileUpdateDate', 'Label' => "Date de mise à jour du profil du contact"),
			array('Name' => 'Sent', 'Label' => "Nombre de message envoyés sur ce contact depuis son inscription"),
			array('Name' => 'LastClickDate', 'Label' => "Date de dernier clic du contact"),
			array('Name' => 'LastOpenDate', 'Label' => "Date de dernière ouverture du contact"),
			
			array('Name' => 'EventDistinct', 'Label' => "Nb de désinscription de la campagne"),
			array('Name' => 'EventTotal', 'Label' => "Nb de désinscription du contact sur la campagne"),
			array('Name' => 'EventDate', 'Label' => "Date de désinscription du contact"),
		);
		
		$fieldsRelate = array(
			array('Name' => 'MemberID', 'Label' => "Identifiant du contact"),
			array('Name' => 'ReportID', 'Label' => "Identifiant de la campagne"),
			array('Name' => 'CampaignChildID', 'Label' => "Numéro de l’envoi"),
			array('Name' => 'SaleMgtID', 'Label' => "Identifiant de l’entité gestionnaire"),
			array('Name' => 'Optin', 'Label' => "Identifiant de l’origine du contact")
		);

		foreach ($fields as $field) {
			$this->moduleFields[$field['Name']] = array(
					'label' => $field['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false
			);
		}

		foreach ($fieldsRelate as $relate) {
			$this->fieldsRelate[$relate['Name']] = array(
					'label' => $relate['Label'],
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false,
					'required_relationship' => 0
			);
		}
		
	} // get_statunsub_fields()
	
	// Récupère les champs du module campagne
	public function get_campagne_fields(){
		$proxywsdl = "http://api.dolist.net/V2/CampaignManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/CampaignManagementService.svc/soap1.1";
						
		$campaignFilter = array(
			'AllCampaigns' => true,
			'Offset' => 0
		);
					
		$optionsRequest = array(
			'filter' => $campaignFilter
		);
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$requestClient = array(
			'token'=> $token,
			'request'=> $optionsRequest
		);
		
		$resultClient = $client->GetCampaigns($requestClient);
		
		if (!is_null($resultClient->GetCampaignsResult) and $resultClient->GetCampaignsResult != '')
		{
			$campagnes = $resultClient->GetCampaignsResult->CampaignDetailsList;
			$campagnes = json_decode(json_encode((array)$campagnes), true);
			
			if(empty($campagnes['CampaignDetails'][0])) throw new \Exception("No data found");
			
			foreach ($campagnes['CampaignDetails'][0] as $field => $value) {
		    	if($field == 'ID') {
		    		$this->fieldsRelate[$field] = array(
							'label' => $field,
							'type' => 'text',
							'type_bdd' => 'varchar(255)',
							'required' => false,
							'required_relationship' => 0
					);
				}
				$this->moduleFields[$field] = array(
						'label' => $field,
						'type' => 'text',
						'type_bdd' => 'varchar(255)',
						'required' => false
				);
			}
		}
		else
		{
			throw new \Exception("No data found");
		}		
	} // get_campagne_fields()
	
	// Récupère les champs du module SGB
	public function get_StaticSegmentBody_fields(){
         $this->fieldsRelate['FileName'] = array(
                                    'label' => 'FileName',
                                    'type' => 'text',
                                    'type_bdd' => 'varchar(255)',
                                    'required' => false,
									'required_relationship' => 1
                                );
         $this->fieldsRelate['ContactID'] = array(
                                    'label' => 'ContactID',
                                    'type' => 'text',
                                    'type_bdd' => 'varchar(255)',
                                    'required' => false,
									'required_relationship' => 1
                                );
	} // get_StaticSegmentBody_fields()
	
	// Récupère les champs du module SGH
	public function get_StaticSegmentHeader_fields(){
		$this->moduleFields['ImportName'] = array(
				'label' => 'ImportName',
				'type' => 'text',
				'type_bdd' => 'varchar(255)',
				'required' => true
		);
		$this->moduleFields['NumberOfTargets'] = array(
				'label' => 'NumberOfTargets',
				'type' => 'text',
				'type_bdd' => 'varchar(255)',
				'required' => true
		);		
		$this->moduleFields["ReportAddresse"] = array(
					'label' => "ReportAddresse",
					'type' => 'text',
					'type_bdd' => 'varchar(255)',
					'required' => false
		);	
	} // get_StaticSegmentHeader_fields()	
			
	// Permet de mettre en forme la date Dolist en date Myddleware
	protected function DateConverter($date){
        $tab = explode('T', $date); // Suppression du T
        $date = $tab[0] . ' ' . $tab[1]; // Ajout du ' ' au milieu
        $tab = explode('.', $date); // Suppression de la fin de la date (après le .)
        return $tab[0];
	} // DateConverter($date)	

	// Permet de récupérer le dernier enregistrement de la solution (utilisé pour tester le flux)
	public function read_last($param) {
		try{
			$result = array();
			if(empty($param['fields'])) {
				$param['fields'] = array();
			}
			try{
				switch ($param['module']) {
					case 'contact':
						$result = $this->get_last_contact($param);
						break;
					case 'contactCible':
						$result = $this->get_last_contact($param);
						break;
					case 'campagne':
						$result = $this->get_last_campagne($param);
						break;
					case 'statopen':
						$result = $this->get_last_statopen($param);
						break;
					case 'statclick':
						$result = $this->get_last_statclick($param);
						break;
					case 'statdelivery':
						$result = $this->get_last_statdelivery($param);
						break;
					case 'stathardbounce':
						$result = $this->get_last_stathardbounce($param);
						break;
					case 'statunsub':
						$result = $this->get_last_statunsub($param);
						break;
					default:
						throw new \Exception("Error Retreiving Module");
						break;
				}
				return $result;
			}
			catch(\SoapFault $fault) {
				$Detail = $fault->detail;
				throw new \Exception($Detail->ServiceException->Description);
			}
		}
		catch (\Exception $e){
			$result['done'] = -1;
			$result['error'] = $e->getMessage();
			return $result;
		}
	} // read_last($param)	

	// Read last contact
	public function get_last_contact($param){
		$proxywsdl = "http://api.dolist.net/V2/ContactManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/ContactManagementService.svc/soap1.1";
						
		$optionsRequest = array(
			'Offset' => 0, //Optionnel: L'indice du 1er contact retourné. 
			'AllFields' => true, //Indique si on doit retourner tous les champs
			'Interest' => true, //Indique si les interets déclarés sont retourné par la requete
			'LastModifiedOnly' => false, //Indique si la requete doit retourner uniquement les derniers contacts modifiés
		);
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);

		if (!empty($param['query'])) {
			if(!empty($param['query']['id'])) {
				$optionsRequest['RequestFilter'] = array('MemberID' => $param['query']['id']);
			}
		}

		$requestClient = array(
			'token'=> $token,
			'request'=> $optionsRequest
		);
		
		$resultClient = $client->GetContact($requestClient);
		
		if (!is_null($resultClient->GetContactResult) and $resultClient->GetContactResult != '')
		{
			$contacts = $resultClient->GetContactResult->ContactList;
			$contacts = json_decode(json_encode((array)$contacts), true);
			
			if(!empty($param['query']['id'])){
				$lastContact = $contacts['ContactData'];
			} else {
				$lastContact = $contacts['ContactData'][0];
			}
			
			if(empty($lastContact)) {
				$result['done'] = false;
			} else {
				foreach ($lastContact['CustomFields']['CustomField'] as $CustomField) {
					if(in_array($CustomField['Name'], $param['fields']))
						$row[$CustomField['Name']] = $CustomField['Value'];
				}
				
				foreach ($lastContact as $field => $value) {
					if($field == 'MemberId')
						$row['id'] = $value;
					if($field == 'UpdateDate')
						$row['date_modified'] = $this->DateConverter($value);
					if(in_array($field, $param['fields']))
						$row[$field] = $value;
				}
				foreach ($param['fields'] as $paramField){
					if(!isset($row[$paramField]))
						$row[$paramField] = '';
				}
				$result['values'] = $row;
				$result['done'] = true;
			}
		}
		else
		{
			throw new \Exception("No data found");
		}
		return $result;	
	} // get_last_contact()	

	// Read last campagne
	public function get_last_campagne($param){
		$proxywsdl = "http://api.dolist.net/V2/CampaignManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/CampaignManagementService.svc/soap1.1";
						
		$campaignFilter = array(
			'AllCampaigns' => true,
			'Offset' => 0
		);
					
		$optionsRequest = array(
			'filter' => $campaignFilter
		);
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$requestClient = array(
			'token'=> $token,
			'request'=> $optionsRequest
		);
		
		$resultClient = $client->GetCampaigns($requestClient);

		if (!is_null($resultClient->GetCampaignsResult) and $resultClient->GetCampaignsResult != '')
		{
			$campagnes = $resultClient->GetCampaignsResult->CampaignDetailsList;
			$campagnes = json_decode(json_encode((array)$campagnes), true);

			if(empty($campagnes['CampaignDetails'][0])) throw new \Exception("No data found");
			
			foreach ($campagnes['CampaignDetails'][0] as $field => $value) {
				if($field == 'CreationDate')
					$row['date_modified'] = $this->DateConverter($value);
				if(in_array($field, $param['fields']))
					$row[$field] = $value;
			}
			$result['values'] = $row;
			$result['done'] = true;
		}
		else
		{
			throw new \Exception("No data found");
		}
		return $result;
	} // get_last_campagne()

	// Read last statopen
	public function get_last_statopen($param){
		// Url du contrat wsdl TOCHANGE après la mise en prod
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$GetCampaignStatsRequest = array(
			'StartDate' => '07/08/2000',
			'EndDate' => date('d/m/Y'), 
			'StaticFieldList' => $param['fields'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		$resultClient = $client->GetOpenStats($CampaignStatsRequest);
		
		if (!$resultClient->GetOpenStatsResult->Count == 0)
		{
			$stats = $resultClient->GetOpenStatsResult->OpenStatsList->OpenStats;
			$stats = json_decode(json_encode((array)$stats), true);
			
			if(empty($stats[0])) throw new \Exception("No data found");
			
			foreach ($stats[0] as $field => $value) {
				if($field == 'EventDate')
					$row['date_modified'] = $value;
				if(in_array($field, $param['fields']))
					$row[$field] = $value;
				if($field == "StaticFieldValueList") {
					if(isset($value['StaticFieldValue'])){
						if(isset($value['StaticFieldValue']['Name'])) {
							$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
						} else {
							foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
								$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
							}
						}
					}
				}
			}
			$result['values'] = $row;
			$result['done'] = true;
		}
		else
		{
			throw new \Exception("No data found");
		}
		return $result;
	} // get_last_statopen()

	// Read last statclick
	public function get_last_statclick($param){
		// Url du contrat wsdl TOCHANGE après la mise en prod
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$GetCampaignStatsRequest = array(
			'StartDate' => '07/08/2000',
			'EndDate' => date('d/m/Y'), 
			'StaticFieldList' => $param['fields'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		$resultClient = $client->GetClickStats($CampaignStatsRequest);
		
		if (!$resultClient->GetClickStatsResult->Count == 0)
		{
			$stats = $resultClient->GetClickStatsResult->ClickStatsList->ClickStats;
			$stats = json_decode(json_encode((array)$stats), true);
			
			if(empty($stats[0])) throw new \Exception("No data found");
			
			foreach ($stats[0] as $field => $value) {
				if($field == 'EventDate')
					$row['date_modified'] = $value;
				if(in_array($field, $param['fields']))
					$row[$field] = $value;
				if($field == "StaticFieldValueList") {
					if(isset($value['StaticFieldValue'])){
						if(isset($value['StaticFieldValue']['Name'])) {
							$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
						} else {
							foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
								$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
							}
						}
					}
				}
			}
			$result['values'] = $row;
			$result['done'] = true;
		}
		else
		{
			throw new \Exception("No data found");
		}
		return $result;
	} // get_last_statclick()

	// Read last statdelivery
	public function get_last_statdelivery($param){
		// Url du contrat wsdl TOCHANGE après la mise en prod
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$GetCampaignStatsRequest = array(
			'StartDate' => '07/08/2000',
			'EndDate' => date('d/m/Y'), 
			'StaticFieldList' => $param['fields'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		$resultClient = $client->GetDeliveryStats($CampaignStatsRequest);
		
		if (!$resultClient->GetDeliveryStatsResult->Count == 0)
		{
			$stats = $resultClient->GetDeliveryStatsResult->DeliveryStatsList->DeliveryStats;
			$stats = json_decode(json_encode((array)$stats), true);
			
			if(empty($stats[0])) throw new \Exception("No data found");
			
			foreach ($stats[0] as $field => $value) {
				if($field == 'EventDate')
					$row['date_modified'] = $value;
				if(in_array($field, $param['fields']))
					$row[$field] = $value;
				if($field == "StaticFieldValueList") {
					if(isset($value['StaticFieldValue'])){
						if(isset($value['StaticFieldValue']['Name'])) {
							$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
						} else {
							foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
								$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
							}
						}
					}
				}
			}
			$result['values'] = $row;
			$result['done'] = true;
		}
		else
		{
			throw new \Exception("No data found");
		}
		return $result;
	} // get_last_statdelivery()

	// Read last stathardbounce
	public function get_last_stathardbounce($param){
		// Url du contrat wsdl TOCHANGE après la mise en prod
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$GetCampaignStatsRequest = array(
			'StartDate' => '07/08/2000',
			'EndDate' => date('d/m/Y'), 
			'StaticFieldList' => $param['fields'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		$resultClient = $client->GetHardbounceStats($CampaignStatsRequest);
		
		if (!$resultClient->GetHardbounceStatsResult->Count == 0)
		{
			$stats = $resultClient->GetHardbounceStatsResult->HardbounceStatsList->HardbounceStats;
			$stats = json_decode(json_encode((array)$stats), true);
			
			if(empty($stats[0])) throw new \Exception("No data found");
			
			foreach ($stats[0] as $field => $value) {
				if($field == 'EventDate')
					$row['date_modified'] = $value;
				if(in_array($field, $param['fields']))
					$row[$field] = $value;
				if($field == "StaticFieldValueList") {
					if(isset($value['StaticFieldValue'])){
						if(isset($value['StaticFieldValue']['Name'])) {
							$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
						} else {
							foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
								$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
							}
						}
					}
				}
			}
			$result['values'] = $row;
			$result['done'] = true;
		}
		else
		{
			throw new \Exception("No data found");
		}
		return $result;
	} // get_last_stathardbounce()
	
	// Read last statunsub
	public function get_last_statunsub($param){
		// Url du contrat wsdl TOCHANGE après la mise en prod
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$GetCampaignStatsRequest = array(
			'StartDate' => '07/08/2000',
			'EndDate' => date('d/m/Y'), 
			'StaticFieldList' => $param['fields'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		$resultClient = $client->GetUnsubscribeStats($CampaignStatsRequest);
		
		if (!$resultClient->GetUnsubscribeStatsResult->Count == 0)
		{
			$stats = $resultClient->GetUnsubscribeStatsResult->UnsubscribeStatsList->UnsubscribeStats;
			$stats = json_decode(json_encode((array)$stats), true);
			
			if(empty($stats[0])) throw new \Exception("No data found");
			
			foreach ($stats[0] as $field => $value) {
				if($field == 'EventDate')
					$row['date_modified'] = $value;
				if(in_array($field, $param['fields']))
					$row[$field] = $value;
				if($field == "StaticFieldValueList") {
					if(isset($value['StaticFieldValue'])){
						if(isset($value['StaticFieldValue']['Name'])) {
							$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
						} else {
							foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
								$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
							}
						}
					}
				}
			}
			$result['values'] = $row;
			$result['done'] = true;
		}
		else
		{
			throw new \Exception("No data found");
		}
		return $result;
	} // get_last_statunsub()
	
	// Permet de récupérer les enregistrements modifiés depuis la date en entrée dans la solution
	public function read($param) {
		$result = array();
		if (empty($param['limit'])) {
			$param['limit'] = 100;
		}
		try{
			$arrayModDate = array('contact', 'campagne');
			if(in_array($param['module'], $arrayModDate)) {
				// mise en forme de la date de référence
				$dateref = $param['date_ref'];
				$tab = explode(' ', $dateref);
				$param['date_ref'] = $tab[0].'T'.$tab[1];
			}
			try{
				switch ($param['module']) {
					case 'contact':
						$result = $this->get_contacts($param);
						break;
					case 'campagne':
						$result = $this->get_campagnes($param);
						break;
					case 'statopen':
						$result = $this->get_statopen($param);
						break;
					case 'statclick':
						$result = $this->get_statclick($param);
						break;
					case 'statdelivery':
						$result = $this->get_statdelivery($param);
						break;
					case 'stathardbounce':
						$result = $this->get_stathardbounce($param);
						break;
					case 'statunsub':
						$result = $this->get_statunsub($param);
						break;
					default:
						throw new \Exception("Error Retreiving Module");
						break;
				}				
				return $result;
			}
			catch(\SoapFault $fault) {
				$Detail = $fault->detail;
				throw new \Exception($Detail->ServiceException->Description);
			}
		}
		catch (\Exception $e){
			$result['error'] = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			return $result;
		}
	} // read($param)	

	// Récupère les données des contacts
	public function get_contacts($param){
		// On va chercher le nom du champ pour la date de référence: Création ou Modification
		$DateRefField = $this->getDateRefName($param['module'], $param['rule']['mode']);
		
		$proxywsdl = "http://api.dolist.net/V2/ContactManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/ContactManagementService.svc/soap1.1";
						
		$optionsRequest = array(
			'Offset' => 0, //Optionnel: L'indice du 1er contact retourné. 
			'AllFields' => true, //Indique si on doit retourner tous les champs
			'Interest' => true, //Indique si les interets déclarés sont retourné par la requete
			'LastModifiedOnly' => false, //Indique si la requete doit retourner uniquement les derniers contacts modifiés
		);
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$requestClient = array(
			'token'=> $token,
			'request'=> $optionsRequest
		);
		
		$resultClient = $client->GetContact($requestClient);
		
		if (!is_null($resultClient->GetContactResult) and $resultClient->GetContactResult != '')
		{
			$contacts = $resultClient->GetContactResult->ContactList;
			$contacts = json_decode(json_encode((array)$contacts), true);
			
			if(empty($contacts['ContactData'][0])) {
				throw new \Exception("No data found");
			}
			
			// Préparation du nextOffset pour la boucle
			$nextOffset = $resultClient->GetContactResult->ReturnContactsCount;

			$a = 0;
			$cpt = 0;
			$row = array();
			foreach ($contacts['ContactData'] as $contact) {
				$a++;
				if($contact[$DateRefField] < $param['date_ref']) continue;
				foreach ($contact['CustomFields']['CustomField'] as $CustomField) {
					if(in_array($CustomField['Name'], $param['fields']))
						$row[$CustomField['Name']] = $CustomField['Value'];
				}
			
				foreach ($contact as $field => $value) {
					if($field == 'MemberId')
						$row['id'] = $value;
					if($field == $DateRefField)
						$row['date_modified'] = $this->DateConverter($value);
					if(in_array($field, $param['fields']))
						$row[$field] = $value;
				}
				if(!isset($result['date_ref']))
					$result['date_ref'] = $row['date_modified'];
				if($row['date_modified'] > $result['date_ref'])
					$result['date_ref'] = $row['date_modified'];
				// Ajout d'une seconde à la date de référence
				$date_ref = date_create($result['date_ref']);
				date_modify($date_ref, '+1 seconde');
				$result['date_ref'] = date_format($date_ref, 'Y-m-d H:i:s');
			    $result['values'][$contact['MemberId']] = $row;
			    $row = array();
				$cpt++;
			}

			// Dolist partitionne ses résultats, si on a pas tous les contacts, on continue
			if($resultClient->GetContactResult->ReturnContactsCount < $resultClient->GetContactResult->TotalContactsCount) {
				// On stocke le nombre de contacts total et le nombre renvoyé par le premier appel déjà exécuté
				$TotalContactsCount = $resultClient->GetContactResult->TotalContactsCount;
				$RetunContactsCount = $resultClient->GetContactResult->ReturnContactsCount;
				// Tant qu'on a pas eu la totalité des résultats
				while ($RetunContactsCount < $TotalContactsCount) {
					$optionsRequest = array(
						'Offset' => $nextOffset, //Optionnel: L'indice du 1er contact retourné. 
						'AllFields' => true, //Indique si on doit retourner tous les champs
						'Interest' => true, //Indique si les interets déclarés sont retourné par la requete
						'LastModifiedOnly' => false, //Indique si la requete doit retourner uniquement les derniers contacts modifiés
					);
					
					$requestClient = array(
						'token'=> $token,
						'request'=> $optionsRequest
					);
					
					$resultClient = $client->GetContact($requestClient);
										
					if (!is_null($resultClient->GetContactResult) and $resultClient->GetContactResult != '')
					{
						// Mise à jour du nombre de Contacts déjà retournés
						$RetunContactsCount = $RetunContactsCount + $resultClient->GetContactResult->ReturnContactsCount;
						
						$contacts = $resultClient->GetContactResult->ContactList;
						$contacts = json_decode(json_encode((array)$contacts), true);
						
						if(empty($contacts['ContactData'][0])) {
							break;
						}
						
						// Préparation du nextOffset pour la boucle
						$nextOffset = $RetunContactsCount;
						
						$row = array();
						foreach ($contacts['ContactData'] as $contact) {
							$a++;
							if($contact[$DateRefField] < $param['date_ref']) continue;
							foreach ($contact['CustomFields']['CustomField'] as $CustomField) {
								if(in_array($CustomField['Name'], $param['fields']))
									$row[$CustomField['Name']] = $CustomField['Value'];
							}
						
							foreach ($contact as $field => $value) {
								if($field == 'MemberId')
									$row['id'] = $value;
								if($field == $DateRefField)
									$row['date_modified'] = $this->DateConverter($value);
								if(in_array($field, $param['fields']))
									$row[$field] = $value;
							}
							if(!isset($result['date_ref']))
								$result['date_ref'] = $row['date_modified'];
							if($row['date_modified'] > $result['date_ref'])
								$result['date_ref'] = $row['date_modified'];
							// Ajout d'une seconde à la date de référence
							$date_ref = date_create($result['date_ref']);
							date_modify($date_ref, '+1 seconde');
							$result['date_ref'] = date_format($date_ref, 'Y-m-d H:i:s');
						    $result['values'][$contact['MemberId']] = $row;
						    $row = array();
							$cpt++;
						}
					}
					else
					{
						break;
					}
				}
			}		
			$result['count'] = $cpt;
			if(!empty($result))
				return $result;
			else
				throw new \Exception("No data found");
		}
		else
		{
			throw new \Exception("No data found");
		}
	} // get_contacts()	

	// Récupère les données des campagnes
	public function get_campagnes($param){
		$proxywsdl = "http://api.dolist.net/V2/CampaignManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/CampaignManagementService.svc/soap1.1";
						
		$campaignFilter = array(
			'AllCampaigns' => true,
			'Offset' => 0
		);
					
		$optionsRequest = array(
			'filter' => $campaignFilter
		);
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$requestClient = array(
			'token'=> $token,
			'request'=> $optionsRequest
		);
		
		$resultClient = $client->GetCampaigns($requestClient);

		if (!is_null($resultClient->GetCampaignsResult) and $resultClient->GetCampaignsResult != '')
		{
			$campagnes = $resultClient->GetCampaignsResult->CampaignDetailsList;
			$campagnes = json_decode(json_encode((array)$campagnes), true);

			if(empty($campagnes['CampaignDetails'][0])) {
				throw new \Exception("No data found");
			}
			$cpt = 0;
			$row = array();
			foreach ($campagnes['CampaignDetails'] as $campagne) {
				if($campagne['CreationDate'] < $param['date_ref']) continue;
				foreach ($campagne as $field => $value) {
					if($field == 'ID')
						$row['id'] = $value;
					if($field == 'CreationDate')
						$row['date_modified'] = $this->DateConverter($value);
					if(in_array($field, $param['fields']))
						$row[$field] = $value;
				}
				if(!isset($result['date_ref']))
					$result['date_ref'] = $row['date_modified'];
				if($row['date_modified'] > $result['date_ref'])
					$result['date_ref'] = $row['date_modified'];
			    $result['values'][$campagne['ID']] = $row;
			    $row = array();
				$cpt++;
			}
			$result['count'] = $cpt;
			if(!empty($result))
				return $result;
			else
				throw new \Exception("No data found");
		}
		else
		{
			throw new \Exception("No data found");
		}
	} // get_campagnes()

	public function changeFormatDate($dateToChange, $oldFormat, $newFormat) {
		$date = \DateTime::createFromFormat($oldFormat, $dateToChange);
		return date_format($date, $newFormat);
	}	
	
	// Récupère les champs du module statopen
	public function get_statopen($param){
		$nbWhile = 0;
		$offset = 0;
		$result['count'] = 0;
		
		// Ajout du champ MemberID dans $param['fields'] pour l'identifiant final
		if(!in_array('MemberID', $param['fields']))
			$param['fields'][] = 'MemberID';
		
		// Url du contrat wsdl
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$StartDate = $this->changeFormatDate($param['date_ref'], 'Y-m-d H:i:s', 'd/m/Y');
		
		$date = new \DateTime();
		$date->modify('+1 day');
		$EndDate = $date->format('d/m/Y');
		$GetCampaignStatsRequest = array(
			'StartDate' => $StartDate,
			'EndDate' => $EndDate,
			'SortField' => 'EventDate',
			'SortOrder' => 'ASC',
			'StaticFieldList' => $param['fields'],
			'Limit' => $param['limit'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		do { 
			// Empty for the first while
			if (!empty($offset)) {
				$CampaignStatsRequest['request']['Offset'] = $offset;
			}
			$resultClient = $client->GetOpenStats($CampaignStatsRequest);
			
			if (!$resultClient->GetOpenStatsResult->Count == 0)
			{
				$stats = $resultClient->GetOpenStatsResult->OpenStatsList->OpenStats;
				$stats = json_decode(json_encode((array)$stats), true);
				
				if(empty($stats)) throw new \Exception("No data found");

				// Lorsqu'il n'y a qu'un seul résultat $stats n'a pas d'indice
				if(empty($stats[0])) {
					$tmp = $stats;
					$stats = array();
					$stats[0] = $tmp;
				}

				$cpt = 0;
				$row = array();
				foreach ($stats as $open) {
					if(($this->changeFormatDate($open['EventDate'], 'd/m/Y H:i:s', 'Y-m-d H:i:s')) <= $param['date_ref']) continue; // Mise en forme de la date
					foreach ($open as $field => $value) {
						if($field == 'EventDate'){
							$row['date_modified'] = $this->changeFormatDate($value, 'd/m/Y H:i:s', 'Y-m-d H:i:s');
						}
						if(in_array($field, $param['fields']))
							$row[$field] = $value;
						if($field == "StaticFieldValueList") {
							if(isset($value['StaticFieldValue'])){
								if(isset($value['StaticFieldValue']['Name'])) {
									$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
								} else {
									foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
										$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
									}
								}
							}
						}
					}
					if(!isset($result['date_ref']))
						$result['date_ref'] = $row['date_modified'];
					if($row['date_modified'] > $result['date_ref'])
						$result['date_ref'] = $row['date_modified'];
					$id = "open.".$open['ReportID'].".".$row['date_modified'].".".$row['MemberID'];
					$id = str_replace(' ', 'T', $id);
					$row['id'] = $id;
					$result['values'][$id] = $row;
					$row = array();
					$cpt++;
				}
				$result['count'] += $cpt;
				$offset += $param['limit'];			
				$nbWhile++;
			}
		}
		// On continue si :
		// 1.    Le nombre de résultat du dernier appel est égal à la limite
		// 2.    La limite de sécurité n'est pas atteinte
		while (
					$resultClient->GetOpenStatsResult->Count == $param['limit']
				&& $nbWhile < $this->noInfiniteWhile   
		);
		// Erreur si on a atteind la limite des appels 
		if ($nbWhile >= $this->noInfiniteWhile ) {
			throw new \Exception("Limit call function reached.");
		}			
		if(!empty($result)) {
			return $result;
		}
		else {
			throw new \Exception("No data found");
		}
	} // get_statopen()	
	
	// Récupère les champs du module statclick
	public function get_statclick($param){
		$nbWhile = 0;
		$offset = 0;
		$result['count'] = 0;
		
		// Ajout du champ MemberID dans $param['fields'] pour l'identifiant final
		if(!in_array('MemberID', $param['fields']))
			$param['fields'][] = 'MemberID';
		
		// Url du contrat wsdl
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$StartDate = $this->changeFormatDate($param['date_ref'], 'Y-m-d H:i:s', 'd/m/Y');
		
		$date = new \DateTime();
		$date->modify('+1 day');
		$EndDate = $date->format('d/m/Y');
		$GetCampaignStatsRequest = array(
			'StartDate' => $StartDate,
			'EndDate' => $EndDate,
			'SortField' => 'EventDate',
			'SortOrder' => 'ASC',
			'StaticFieldList' => $param['fields'],
			'Limit' => $param['limit'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		do { 
			// Empty for the first while
			if (!empty($offset)) {
				$CampaignStatsRequest['request']['Offset'] = $offset;
			}
			$resultClient = $client->GetClickStats($CampaignStatsRequest);
			
			if (!$resultClient->GetClickStatsResult->Count == 0)
			{
				$stats = $resultClient->GetClickStatsResult->ClickStatsList->ClickStats;
				$stats = json_decode(json_encode((array)$stats), true);
				
				if(empty($stats)) throw new \Exception("No data found");
				
				// Lorsqu'il n'y a qu'un seul résultat $stats n'a pas d'indice
				if(empty($stats[0])) {
					$tmp = $stats;
					$stats = array();
					$stats[0] = $tmp;
				}
				
				$cpt = 0;
				$row = array();
				foreach ($stats as $open) {
					if(($this->changeFormatDate($open['EventDate'], 'd/m/Y H:i:s', 'Y-m-d H:i:s')) <= $param['date_ref']) continue; // Mise en forme de la date
					foreach ($open as $field => $value) {
						if($field == 'EventDate'){
							$row['date_modified'] = $this->changeFormatDate($value, 'd/m/Y H:i:s', 'Y-m-d H:i:s');
						}
						if(in_array($field, $param['fields']))
							$row[$field] = $value;
						if($field == "StaticFieldValueList") {
							if(isset($value['StaticFieldValue'])){
								if(isset($value['StaticFieldValue']['Name'])) {
									$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
								} else {
									foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
										$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
									}
								}
							}
						}
					}
					if(!isset($result['date_ref']))
						$result['date_ref'] = $row['date_modified'];
					if($row['date_modified'] > $result['date_ref'])
						$result['date_ref'] = $row['date_modified'];
					$id = "click.".$open['ReportID'].".".$row['date_modified'].".".$row['MemberID'];
					$id = str_replace(' ', 'T', $id);
					$row['id'] = $id;
					$result['values'][$id] = $row;
					$row = array();
					$cpt++;
				}
				$result['count'] += $cpt;
				$offset += $param['limit'];			
				$nbWhile++;
			}
		}
		// On continue si :
		// 1.    Le nombre de résultat du dernier appel est égal à la limite
		// 2.    La limite de sécurité n'est pas atteinte
		while (
					$resultClient->GetClickStatsResult->Count == $param['limit']
				&& $nbWhile < $this->noInfiniteWhile   
		);
		// Erreur si on a atteind la limite des appels 
		if ($nbWhile >= $this->noInfiniteWhile ) {
			throw new \Exception("Limit call function reached.");
		}			
		if(!empty($result)) {
			return $result;
		}
		else {
			throw new \Exception("No data found");
		}
	} // get_statclick()	
	
	// Récupère les champs du module stathardbounce
	public function get_stathardbounce($param){	
		$nbWhile = 0;
		$offset = 0;
		$result['count'] = 0;
		
		// Ajout du champ MemberID dans $param['fields'] pour l'identifiant final
		if(!in_array('MemberID', $param['fields']))
			$param['fields'][] = 'MemberID';
		
		// Url du contrat wsdl
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$StartDate = $this->changeFormatDate($param['date_ref'], 'Y-m-d H:i:s', 'd/m/Y');
		
		$date = new \DateTime();
		$date->modify('+1 day');
		$EndDate = $date->format('d/m/Y');
		$GetCampaignStatsRequest = array(
			'StartDate' => $StartDate,
			'EndDate' => $EndDate,
			'SortField' => 'EventDate',
			'SortOrder' => 'ASC',
			'StaticFieldList' => $param['fields'],
			'Limit' => $param['limit'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);
		
		do { 
			// Empty for the first while
			if (!empty($offset)) {
				$CampaignStatsRequest['request']['Offset'] = $offset;
			}
			$resultClient = $client->GetHardbounceStats($CampaignStatsRequest);
			
			if (!$resultClient->GetHardbounceStatsResult->Count == 0)
			{
				$stats = $resultClient->GetHardbounceStatsResult->HardbounceStatsList->HardbounceStats;
				$stats = json_decode(json_encode((array)$stats), true);
				
				if(empty($stats)) throw new \Exception("No data found");
				
				// Lorsqu'il n'y a qu'un seul résultat $stats n'a pas d'indice
				if(empty($stats[0])) {
					$tmp = $stats;
					$stats = array();
					$stats[0] = $tmp;
				}
				
				$cpt = 0;
				$row = array();
				foreach ($stats as $open) {
					if(($this->changeFormatDate($open['EventDate'], 'd/m/Y H:i:s', 'Y-m-d H:i:s')) <= $param['date_ref']) continue; // Mise en forme de la date
					foreach ($open as $field => $value) {
						if($field == 'EventDate'){
							$row['date_modified'] = $this->changeFormatDate($value, 'd/m/Y H:i:s', 'Y-m-d H:i:s');
						}
						if(in_array($field, $param['fields']))
							$row[$field] = $value;
						if($field == "StaticFieldValueList") {
							if(isset($value['StaticFieldValue'])){
								if(isset($value['StaticFieldValue']['Name'])) {
									$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
								} else {
									foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
										$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
									}
								}
							}
						}
					}
					if(!isset($result['date_ref']))
						$result['date_ref'] = $row['date_modified'];
					if($row['date_modified'] > $result['date_ref'])
						$result['date_ref'] = $row['date_modified'];
					$id = "hardbounce.".$open['ReportID'].".".$row['MemberID'];
					$row['id'] = $id;
					$result['values'][$id] = $row;
					$row = array();
					$cpt++;
				}
				$result['count'] += $cpt;
				$offset += $param['limit'];			
				$nbWhile++;
			}
		}
		// On continue si :
		// 1.    Le nombre de résultat du dernier appel est égal à la limite
		// 2.    La limite de sécurité n'est pas atteinte
		while (
					$resultClient->GetHardbounceStatsResult->Count == $param['limit']
				&& $nbWhile < $this->noInfiniteWhile   
		);
		// Erreur si on a atteind la limite des appels 
		if ($nbWhile >= $this->noInfiniteWhile ) {
			throw new \Exception("Limit call function reached.");
		}			
		if(!empty($result)) {
			return $result;
		}
		else {
			throw new \Exception("No data found");
		}

	} // get_stathardbounce()
	
	// Récupère les champs du module statdelivery
	public function get_statdelivery($param){
		$nbWhile = 0;
		$offset = 0;
		$result['count'] = 0;

		// Ajout du champ MemberID dans $param['fields'] pour l'identifiant final
		if(!in_array('MemberID', $param['fields'])) {
			$param['fields'][] = 'MemberID';
		}
		
		// Url du contrat wsdl
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$StartDate = $this->changeFormatDate($param['date_ref'], 'Y-m-d H:i:s', 'd/m/Y');
		
		$date = new \DateTime();
		$date->modify('+1 day');
		$EndDate = $date->format('d/m/Y');
		$GetCampaignStatsRequest = array(
			'StartDate' => $StartDate,
			'EndDate' => $EndDate,
			'SortField' => 'EventDate',
			'SortOrder' => 'ASC',
			'StaticFieldList' => $param['fields'],
			'Limit' => $param['limit'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		do { 
			// Empty for the first while
			if (!empty($offset)) {
				$CampaignStatsRequest['request']['Offset'] = $offset;
			}
			$resultClient = $client->GetDeliveryStats($CampaignStatsRequest);	
			
			if (!$resultClient->GetDeliveryStatsResult->Count == 0) {
				$stats = $resultClient->GetDeliveryStatsResult->DeliveryStatsList->DeliveryStats;
				$stats = json_decode(json_encode((array)$stats), true);

				if(empty($stats)) throw new \Exception("No data found");
				
				// Lorsqu'il n'y a qu'un seul résultat $stats n'a pas d'indice
				if(empty($stats[0])) {
					$tmp = $stats;
					$stats = array();
					$stats[0] = $tmp;
				}
				
				$cpt = 0;
				$row = array();
				foreach ($stats as $open) {
					if(($this->changeFormatDate($open['EventDate'], 'd/m/Y H:i:s', 'Y-m-d H:i:s')) <= $param['date_ref']) continue; // Mise en forme de la date
					foreach ($open as $field => $value) {
						if($field == 'EventDate'){
							$row['date_modified'] = $this->changeFormatDate($value, 'd/m/Y H:i:s', 'Y-m-d H:i:s');
						}
						if(in_array($field, $param['fields']))
							$row[$field] = $value;
						if($field == "StaticFieldValueList") {
							if(isset($value['StaticFieldValue'])){
								if(isset($value['StaticFieldValue']['Name'])) {
									$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
								} else {
									foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
										$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
									}
								}
							}
						}
					}
					if(!isset($result['date_ref']))
						$result['date_ref'] = $row['date_modified'];
					if($row['date_modified'] > $result['date_ref'])
						$result['date_ref'] = $row['date_modified'];
					$id = "delivery.".$open['ReportID'].".".$row['MemberID'];
					$row['id'] = $id;
					$result['values'][$id] = $row;
					$row = array();
					$cpt++;
				}
				$result['count'] += $cpt;
				$offset += $param['limit'];			
				$nbWhile++;
			}
		}
		// On continue si :
		// 1.    Le nombre de résultat du dernier appel est égal à la limite
		// 2.    La limite de sécurité n'est pas atteinte
		while (
					$resultClient->GetDeliveryStatsResult->Count == $param['limit']
				&& $nbWhile < $this->noInfiniteWhile   
		);
		// Erreur si on a atteind la limite des appels 
		if ($nbWhile >= $this->noInfiniteWhile ) {
			throw new \Exception("Limit call function reached.");
		}			
		if(!empty($result)) {
			return $result;
		}
		else {
			throw new \Exception("No data found");
		}
	} // get_statdelivery()

	// Récupère les champs du module statunsub
	public function get_statunsub($param){
		$nbWhile = 0;
		$offset = 0;
		$result['count'] = 0;
		
		// Ajout du champ MemberID dans $param['fields'] pour l'identifiant final
		if(!in_array('MemberID', $param['fields']))
			$param['fields'][] = 'MemberID';
		
		// Url du contrat wsdl
		$proxywsdl = "http://api.dolist.net/v2/StatisticsService.svc?wsdl";
		$location = "http://api.dolist.net/v2/StatisticsService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		$StartDate = $this->changeFormatDate($param['date_ref'], 'Y-m-d H:i:s', 'd/m/Y');
		
		$date = new \DateTime();
		$date->modify('+1 day');
		$EndDate = $date->format('d/m/Y');
		$GetCampaignStatsRequest = array(
			'StartDate' => $StartDate,
			'EndDate' => $EndDate,
			'SortField' => 'EventDate',
			'SortOrder' => 'ASC',
			'StaticFieldList' => $param['fields'],
			'Limit' => $param['limit'],
		);
		
		$CampaignStatsRequest = array(
			'authenticationTokenContext' => $token,
			'request' => $GetCampaignStatsRequest
		);

		do { 
			// Empty for the first while
			if (!empty($offset)) {
				$CampaignStatsRequest['request']['Offset'] = $offset;
			}
			$resultClient = $client->GetUnsubscribeStats($CampaignStatsRequest);
			
			if (!$resultClient->GetUnsubscribeStatsResult->Count == 0)
			{
				$stats = $resultClient->GetUnsubscribeStatsResult->UnsubscribeStatsList->UnsubscribeStats;
				$stats = json_decode(json_encode((array)$stats), true);
				
				if(empty($stats)) throw new \Exception("No data found");
				
				// Lorsqu'il n'y a qu'un seul résultat $stats n'a pas d'indice
				if(empty($stats[0])) {
					$tmp = $stats;
					$stats = array();
					$stats[0] = $tmp;
				}
				
				$cpt = 0;
				$row = array();
				foreach ($stats as $open) {
					if(($this->changeFormatDate($open['EventDate'], 'd/m/Y H:i:s', 'Y-m-d H:i:s')) <= $param['date_ref']) continue; // Mise en forme de la date
					foreach ($open as $field => $value) {
						if($field == 'EventDate'){
							$row['date_modified'] = $this->changeFormatDate($value, 'd/m/Y H:i:s', 'Y-m-d H:i:s');
						}
						if(in_array($field, $param['fields']))
							$row[$field] = $value;
						if($field == "StaticFieldValueList") {
							if(isset($value['StaticFieldValue'])){
								if(isset($value['StaticFieldValue']['Name'])) {
									$row[$value['StaticFieldValue']['Name']] = $value['StaticFieldValue']['Value'];
								} else {
									foreach ($value['StaticFieldValue'] as $StaticFieldValue) {
										$row[$StaticFieldValue['Name']] = $StaticFieldValue['Value'];
									}
								}
							}
						}
					}
					if(!isset($result['date_ref']))
						$result['date_ref'] = $row['date_modified'];
					if($row['date_modified'] > $result['date_ref'])
						$result['date_ref'] = $row['date_modified'];
					$id = "unsub.".$open['ReportID'].".".$row['MemberID'];
					$row['id'] = $id;
					$result['values'][$id] = $row;
					$row = array();
					$cpt++;
				}
				$result['count'] += $cpt;
				$offset += $param['limit'];			
				$nbWhile++;
			}
		}
		// On continue si :
		// 1.    Le nombre de résultat du dernier appel est égal à la limite
		// 2.    La limite de sécurité n'est pas atteinte
		while (
					$resultClient->GetUnsubscribeStatsResult->Count == $param['limit']
				&& $nbWhile < $this->noInfiniteWhile   
		);
		// Erreur si on a atteind la limite des appels 
		if ($nbWhile >= $this->noInfiniteWhile ) {
			throw new \Exception("Limit call function reached.");
		}			
		if(!empty($result)) {
			return $result;
		}
		else {
			throw new \Exception("No data found");
		}
	} // get_statunsub()
	
	// Permet de créer des données
	public function create($param) {
		try{
			if(!(isset($param['data']))) throw new \Exception ('Data missing for create');
			switch ($param['module']) {
				case 'contactCible':
					$result = $this->create_contacts($param);
					break;
				case 'CampagneCible':
					$result = $this->create_campagnes($param);
					break;
				case 'StaticSegmentHeader':
					$result = $this->create_StaticSegmentHeader($param);
					break;
				case 'StaticSegmentBody':
					$result = $this->create_StaticSegmentBody($param);
					break;
				default:
					throw new \Exception("Error Retreiving Module");
					break;
			}
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
		}
		return $result;
	} // create($param)

	// Créer des contacts
	public function create_contacts($param) {
		// Url du contrat wsdl
		$proxywsdl = "http://api.dolist.net/V2/ContactManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/ContactManagementService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));

		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		foreach($param['data'] as $idDoc => $data) {
			try{
				try{
					// Check control before create
					$data = $this->checkDataBeforeCreate($param, $data);
				    $fields = array();
				    foreach ($data as $key => $value) {
						$fields[] = array(
								'Name' => $key,
								'Value' => $value);
					}
					
					// Erreur si pas de mail sur le contact (clé primaire)
					if (empty($data['email'])) {
						throw new \Exception('No email address. Failed to create contact in Dolist.');						
					}
					
					$contact = array(
						'Email' => $data['email'],
						'Fields' => $fields,
						'InterestsToAdd' => array(), //la liste des identifiants des interets déclarés à associer au contact
						'InterestsToDelete' => array(), //la liste des identifiants des interets déclarés à supprimer sur le contact
						'OptoutEmail' => 0, //0: inscription, 1:désinscription
						'OptoutMobile'=> 0 //0: inscription, 1:désinscription
					);
					
					$contactRequest = array(
						'token'=> $token,
						'contact'=> $contact
					);
					
					$resultSOAP = $client->SaveContact($contactRequest);

					if (empty($resultSOAP->SaveContactResult)) {
						$result[$idDoc] = array(
												'id' => '-1',
												'error' => 'Failed to create Data in Dolist'
										);
					} else  { // réussite
						$ticket = $resultSOAP->SaveContactResult;
						$contactRequest = array(
							'token'=> $token,
							'ticket'=> $ticket
						);
						$resultContact = $client->GetStatusByTicket($contactRequest);
						// La bonne réception des infos du contact sauvegardé peut parfois prendre du temps
						if($resultContact->GetStatusByTicketResult->MemberId == 0){
							sleep(5);
							$resultContact = $client->GetStatusByTicket($contactRequest);
							if($resultContact->GetStatusByTicketResult->MemberId == 0) {
								if(empty ($resultContact->GetStatusByTicketResult->Description)) {
									throw new \Exception("No Saved Contact Info. The save could fail, please check your contacts to send.");
								} else {
									throw new \Exception($resultContact->GetStatusByTicketResult->Description);
								}
							}
						}
						$result[$idDoc] = array(
												'id' => $resultContact->GetStatusByTicketResult->MemberId,
												'error' => false
										);
					}
				} catch(\SoapFault $fault) {
					$Detail = $fault->detail;
					throw new \Exception($Detail->ServiceException->Description);
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}		
		return $result;
	} // create_contacts($param) 	

	// Créer des campagnes
	public function create_campagnes($param) {
		// Url du contrat wsdl
		$proxywsdl = "http://api.dolist.net/V2/CampaignManagementService.svc?wsdl";
		$location = "http://api.dolist.net/V2/CampaignManagementService.svc/soap1.1";
		
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));

		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		foreach($param['data'] as $idDoc => $data) {
			try{
				try{
					// Check control before create
					$data = $this->checkDataBeforeCreate($param, $data);
					$CampaignEmail = array();
				    foreach ($data as $key => $value) {
						if($key == 'target_id') continue;
						if($key == 'Message') {
							$CampaignEmail['Message']['Id'] = $value;
							continue;
						}
						$CampaignEmail[$key] = $value;
					}

					$ccampaignRequest = array(
						'token'=> $token,
						'campaignEmail'=> $CampaignEmail
					);
					
					$resultSOAP = $client->CreateCampaign($ccampaignRequest);
					
					if (empty($resultSOAP->CreateCampaignResult)) {
						$result[$idDoc] = array(
												'id' => '-1',
												'error' => 'Failed to create Data in Dolist'
										);
					} else  { // réussite
						$result[$idDoc] = array(
												'id' => $resultSOAP->CreateCampaignResult,
												'error' => false
										);
					}
				} catch(\SoapFault $fault) {
					if(isset($fault->detail)) {
						$Detail = $fault->detail;
						throw new \Exception($Detail->ServiceException->Description);
					} else 
						throw new \Exception($fault->faultstring);
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		return $result;
	} // create_campagnes($param) 	
	
	// Créer des entêtes de Segments Statiques	
	public function create_StaticSegmentHeader($param) {

		$proxywsdl = "http://api.dolist.net/V2/ImportService.svc?wsdl";
		$location = "http://api.dolist.net/V2/ImportService.svc/soap1.1";
		
		// Génération du proxy
		$client = new \SoapClient($proxywsdl, array('trace' => 1, 'location' => $location));
		 
		// Création du jeton
		$token = array(
			'AccountID' => $this->accountId,
			'Key' => $this->token
		);
		
		foreach($param['data'] as $idDoc => $data) {
			try{	
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				// 2 possibilité pour ce module 
				// Soit target_id est vide et l'on doit créer le fichier en récupérant son nom depuis Dolist
				if (empty($data['target_id'])) {
				
					if(!isset($data['ReportAddresse'])){
						$data['ReportAddresse'] = '';
					}
					
					// Création d'un import
					$importFile = array(
						'ImportName' => $data['ImportName'],
						'CreateSegment' => true,
						'UpdateContacts' => true, 
						'ReportAddresses' => array($data['ReportAddresse']), // $ReportAddresses, //maximum 3 adresses mail
						//Partie location
						'IsRent' => false,
						'RentCredit' => 0,
						'ProviderFileName' => ''
					);
					
					// Création de la requête
					$createImportRequest = array(
						'token' => $token,
						'importFile' => $importFile
					);
					
					// Création d'un import
					$ImportResult = $client->CreateImport($createImportRequest);

					if (!empty($ImportResult->CreateImportResult)) { // condition réussite
						$result[$idDoc] = array(
												'id' => $ImportResult->CreateImportResult->FileName,
												'error' => false
										);
						// Création du fichier dans le dossier tmp
						// Si le fichier n'existe pas on le crée et on y écrit l'entête
						$file = $file = __DIR__.'/../tmp/dolist'.$ImportResult->CreateImportResult->FileName;
						if(!(file_exists($file))) { 
							// Création du fichier vide
							$fp = fopen($file, 'wb');
							// fwrite($fp, 'Data for the file '.$ImportResult->CreateImportResult->FileName.chr(13).chr(10)); // Ajout du dernier champ et du saut de ligne
							fclose($fp);
						}
						// On laisse le document en ready to send. Il passera en send que lorsque le fichier aura bien été envoyé dans Dolist
						$this->updateDocumentStatus($idDoc,$result[$idDoc],$param, 'Ready_to_send');							
					}
					else  {
						$result[$idDoc] = array(
												'id' => '-1',
												'error' => 'Failed to create StaticSegmentHeader in Dolist.'
										);
						$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
					}
					
				}
				// Soit target_id est renseigné et cela signifie que le fichier a déjà été créé, il faut alors l'envoyer si le nombre de données attentdues est trouvé
				else {
					// Vérification de l'existance du fichier
					$file = __DIR__.'/../tmp/dolist'.$data['target_id'];
					if(!(file_exists($file))) { 
						throw new \Exception('Failed to find the file '.$file);
					}
					// On compte le nombre de lignes du fichier
					$lines = file($file);
					$n = count($lines) - 1; // Nombre de lignes, soustraire une pour l'entête NE PAS OUBLIER				
					// On envoie le fichier sur le ftp que si le nombre de targets correspond à celui attendu	
					if ($n == $data['NumberOfTargets']) { 
						$sendFileFtp = $this->sendFileFtp($file,$data['target_id']);
						$result[$idDoc] = array(
								'id' => $data['target_id'],
								'error' => $sendFileFtp
						);
						$this->afterSendFile($idDoc,$param);
						$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);
					}
					else {
						$result[$idDoc] = array(
									'id' => $data['target_id'],
									'error' => 'The file is still pending because there is only '.$n.' lines in this file. '.$data['NumberOfTargets'].' lines expected.'
							);
						$this->updateDocumentStatus($idDoc,$result[$idDoc],$param, 'Ready_to_send');
					}
				}
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
				$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);
			}	
		}		
		return $result;
	} // create_StaticSegmentHeader($param) 	

	protected function sendFileFtp($file,$filename){
		$conn_id = ftp_connect($this->dolistFtpHost);
		$login_result = @ftp_login(
			$conn_id, 
			$this->login,
			$this->password
		);					
		if ((!$conn_id) || (!$login_result)) {
			ftp_close($conn_id);
			throw new \Exception ('Problem occured trying to access to the dolist FTP', 555);
		}
		ftp_pasv($conn_id, true);
		if (! ftp_chdir($conn_id, $this->dolistFtpUploadDir)) {
			throw new \Exception ('Problem occured trying to access the upload directory', 555);
		}
		$upload = ftp_put($conn_id, $filename, $file, FTP_BINARY); 
		if (!$upload) { 
			ftp_close($conn_id);
			throw new \Exception ('Problem occured trying to upload the file', 555);
		}
		unlink($file);
		ftp_close($conn_id);	
		return 'File successfully sent to Dolist';
	}
	

	/*
	 * Boucle sur les relations, un index de result par relation, fichier écrit au fur et à mesure
	 * envoi du fichier avant le return result SI ET SEULEMENT SI on a bien le nombre de contact attendu
	 */ 		
	// Créer des corps de Segments Statiques	
	public function create_StaticSegmentBody($param) {
		// Connection à la BD et Id de la Rule courante
		$ruleId = $param['ruleId'];
		try {
			// Récupération du ruleId de la règle entre Contacts pour récupérer ensuite les infos du contact courant				
			$sqlFields = "SELECT field_id 
							FROM RuleRelationShip 
							WHERE 
									id = :ruleId
								AND id IS NOT NULL
								AND field_name_target = 'ContactID'";
			$stmt = $this->conn->prepare($sqlFields);
			$stmt->bindValue(":ruleId", $ruleId);
			$stmt->execute();	   				
			$ruleRelationContactID = $stmt->fetch();
			if(!empty($ruleRelationContactID['field_id'])) {
				$idRuleContacts = $ruleRelationContactID['field_id'];
			} else {
				throw new \Exception ('Failed to find the Contact Rule');
			}
			
			// Récupération du nom, de la version et du conneceur source de la règle (Contacts vers Contacts)
			$sqlSource = "SELECT name_slug, version, module_source, Solution.name, Connector.id
							FROM Rule
								INNER JOIN Connector
									ON Rule.conn_id_source = Connector.id
								INNER JOIN Solution
									ON Connector.sol_id = Solution.id
							WHERE 
									id = :ruleId";
			$stmt = $this->conn->prepare($sqlSource);
			$stmt->bindValue(":ruleId", $idRuleContacts);
			$stmt->execute();	   				
			$ruleData = $stmt->fetch();
			if(empty($ruleData)) {
				throw new \Exception ('Wrong Contact Rule Data. ');
			}

			// Connexion à la solution source d'un règle définie
			$solutionSource = $this->connectSolution($ruleData['id'],$ruleData['name'],$param['key']);	
			if(!$solutionSource->connexion_valide) {
				throw new \Exception ('Failed to connect to the source connexion. Failed to get data from the source solution. ');
			}
				
			// Lecture des champs de la règle
			$sqlFields = "SELECT * 
							FROM RuleField 
							WHERE id = :ruleId";
			$stmt = $this->conn->prepare($sqlFields);
			$stmt->bindValue(":ruleId", $idRuleContacts);
			$stmt->execute();	   				
			$ruleFields = $stmt->fetchAll();
		
			if(empty($ruleFields)) {
				throw new \Exception ('Failed to get field on the rule. ');
			}
			foreach ($ruleFields as $RuleField) { 
				// Plusieurs champs source peuvent être utilisé pour un seul champ cible
				$fields = explode(";", $RuleField['source_field_name']);
				foreach ($fields as $field) {
					$sourceFields[] = ltrim($field);
				}
			}
		}
		catch (\Exception $e) {
			return array('error' => 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )');
		}
		// Parcours de chaque relation contact - campagne
		foreach($param['data'] as $idDoc => $data) {
			try{
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$fields = array();				
				// Récupération de l'Id du document contenant les infos du contacts					
				$sqlSource = "SELECT source_id 
								FROM Document 
								WHERE 
										id = :ruleId
									AND target_id = :target_id
									AND global_status = 'Close'
								ORDER BY source_date_modified DESC";
				$stmt = $this->conn->prepare($sqlSource);
				$stmt->bindValue(":ruleId", $idRuleContacts);
				$stmt->bindValue(":target_id", $data['ContactID']);
				$stmt->execute();	   				
				$contactsDoc = $stmt->fetchALL();
				if(empty ($contactsDoc)) {
					throw new \Exception ('Relationship failed, please check your contacts status');
				}
				
				// Lescture des données du contact dans la solution source
				$read['module'] = $ruleData['module_source'];
				$read['fields'] = $sourceFields;
				// Avec Dolist il peut y avoir plusieurs contacts dae la source pour un seul contact Dolist
				// En effet Dolist dédoublonne avec l'email
				// Donc on vérifie tous les contacts tant que l'on en a pas trouvé dans la cible (on peut avoir fusionné des doublons) 				
				foreach($contactsDoc as $contactDoc) {				
					$read['query'] = array('id' => $contactDoc['source_id']);	
					$dataSource = $solutionSource->read_last($read);	
					if(!empty($dataSource['values'])) {
						break;
					}			
				}

				// Création du tableau fields pour écriture dans le fichier 
				if(empty($dataSource['values'])) {
					throw new \Exception ('Failed to read date in source solution. ');
				}
				$contactData = $dataSource['values'];

				// Génération du fichier
				$file = __DIR__.'/../tmp/dolist'.$data['FileName'];
				if(!(file_exists($file))) { 
					throw new \Exception ('Failed to find the file dolist'.$data['FileName'].'. Failed to write date in this file. ');
				}
				$fp = fopen($file, 'a+');	
				
				// Si le fichier est vide on écrit alors l'entete
				$lines = file($file);
				$n = count($lines);							
				if ($n == 0) {
					// récupération de la règle du contact
					foreach ($param['ruleRelationships'] as $ruleRelationships) {					
						if ($ruleRelationships['field_name_target'] == 'ContactID') {
							$idRuleContacts = $ruleRelationships['field_id'];
						}
					}
					if (empty($idRuleContacts)) {
						throw new \Exception ('Failed to find the Contact Rule. Failed to write the hearder of the file. ');
					}
					// Récupération des informations cryptées de la règle (Contacts vers Contacts)
					$sqlSource = "SELECT *
									FROM RuleField
									WHERE id = :ruleId";
					$stmt = $this->conn->prepare($sqlSource);
					$stmt->bindValue(":ruleId", $idRuleContacts);
					$stmt->execute();	   				
					$fieldsName = $stmt->fetchAll();
					$entete = '';
					foreach($fieldsName as $filedName) {
						$entete .= $filedName['target_field_name'].chr(9);
					}
					// Supression de la dernière tabulation
					$entete = rtrim($entete);
					fwrite($fp, $entete.chr(13).chr(10));
				}						

				// parcours des champs de chaque contact
				for ($i=0; $i < count($sourceFields) - 1; $i++) { 
					fwrite($fp, $contactData[$sourceFields[$i]].chr(9));
				}
				// Ajout du dernier champ et du saut de ligne
				fwrite($fp, $contactData[$sourceFields[count($sourceFields) - 1]].chr(13).chr(10)); 
				fclose($fp);						
				$result[$idDoc] = array(
											'id' => $idDoc,
											'error' => false
									);
			}
			catch (\Exception $e) {
				$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
			}
			// Modification du statut du flux
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}	
		return $result;
	} // create_StaticSegmentBody($param) 
	
	protected function afterSendFile($idDoc,$param) {
	}
	
	// Permet de modifier des données
	public function update($param) {
		try{
			if(!(isset($param['data']))) throw new \Exception ('Data missing for create');
			switch ($param['module']) {
				case 'staticSegment':
					$result = $this->create_staticSegment($param);
					break;
				case 'contactCible':
					$result = $this->create_contacts($param);
					break;
				default:
					throw new \Exception("Error Retreiving Module");
					break;
			}
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )';
			$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);
		}
		return $result;
	} // update($param)	

	/* public function getDocumentButton($idDocument) {	
		$documentData = $this->getInfoDocument($idDocument);
		if ($documentData['module_target'] == 'StaticSegmentHeader') {
			return array(
					array(
						'name' => 'unlockList',
						'label' => 'Unlock list',
						'solution' => 'dolist',
						'action' => 'unlockList'
					),
				);
		}
	}
	
	
	public function unlockList($idDocument) {
		return true;
	}	 */
	
	// Permet de se connecter à la solution 
	protected function connectSolution($conn_id, $sol_name, $paramKey) {
		// RECUPERE LES PARAMS DE CONNEXION
		$sqlParam = "SELECT id, conn_id, name, value
				FROM ConnectorParam 
				WHERE conn_id = :connId";
		$stmt = $this->conn->prepare($sqlParam);
		$stmt->bindValue("connId", $conn_id);
		$stmt->execute();	    
		$tab_params = $stmt->fetchAll();
		if(empty($tab_params)) {
			throw new \Exception ('Failed to get param of the connector source. ');
		}
		
		// Décryptage des paramètres de connexion
		$paramsConn = array();
		if(!empty($tab_params)) {
			foreach ($tab_params as $key => $value) {
				$paramsConn[$value['name']] = $value['value'];
				$paramsConn['ids'][$value['name']] = array('id' => $value['id'],'conn_id' => $value['conn_id']);
			}			
		}	
		$solutionSource = $this->container->get('myddleware_rule.'.$sol_name);		
		$solutionSource->login($paramsConn);				
		return $solutionSource;
	}
	

	// Permet de renvoyer le mode de la règle en fonction du module target
	// Valeur par défaut "0"
	// Si la règle n'est qu'en création, pas en modicication alors le mode est C
	public function getRuleMode($module,$type) {
		if(
				$type == 'target'
			&&	in_array($module, array('CampagneCible','StaticSegmentBody','StaticSegmentHeader'))
		) { // Si le module n'est pas contact alors c'est uniquement de la création
			return array(
				'C' => 'create_only'
			);
		}
		return parent::getRuleMode($module,$type);
	}
	
	// Renvoie le nom du champ de la date de référence en fonction du module et du mode de la règle
	public function getDateRefName($moduleSource, $RuleMode) {
		if($moduleSource != "contact") {
			return null;
		} else if ($RuleMode == "0"){
			return "UpdateDate";
		} else if ($RuleMode == "C"){
			return "SubscribeDate";
		} else {
			throw new \Exception ("$RuleMode is not a correct Rule mode.");
		}
		return null;
	}
		
	// Fonction permettant de faire l'appel REST
	protected function call($method, $parameters){
    } // call($method, $parameters)
}// class dolistcore

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/dolist.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class dolist extends dolistcore {
	}// class dolistcore
}