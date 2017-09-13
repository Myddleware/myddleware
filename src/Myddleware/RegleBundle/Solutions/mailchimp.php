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
use Symfony\Component\HttpFoundation\Session\Session;

class mailchimpcore  extends solution { 
	public $callback = true;
	public $js = true;
	public $nameFieldGet = 'code';
	
	protected $clientid;
	protected $clientsecret;
	protected $redirect_uri;
	protected $access_token;
	protected $api_endpoint;
	protected $dc;
  
    protected $verifySsl = false;

	public function getFieldsLogin() {	
		return array(
					array(
                            'name' => 'clientid',
                            'type' => 'text',
                            'label' => 'solution.fields.clientid'
                        ),
					array(
                            'name' => 'clientsecret',
                            'type' => 'password',
                            'label' => 'solution.fields.clientsecret'
                        ),
					array(
                            'name' => 'redirect_uri',
                            'type' => 'text',
                            'label' => 'solution.fields.redirect_uri'
                        )
		);
	}
	
	public function login($paramConnexion) {
		parent::login($paramConnexion);
		try {
			$this->init($this->paramConnexion);
			// If we don't have the token, we are in the process of login otherwise we just connect to Mailchimp to read/write data
			if (empty($this->paramConnexion['token'])) {
				$session = $this->container->get('session');
				$myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
				// We always add data again in session because these data are removed after the call of the get
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
				$myddlewareSession['param']['myddleware']['connector']['mailchimp'][$paramConnexion['redirect_uri']]['paramConnexion'] = $this->paramConnexion;
				$myddlewareSession['param']['myddleware']['connector']['solution']['callback'] = 'mailchimp';		
				$session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);	
			} else {	
				// Call Mailchimp to get the api endpoint
				$metadata = $this->call('https://login.mailchimp.com/oauth2/metadata', 'POST', array('oauth_token' => $this->access_token));				
				if (empty($metadata['api_endpoint'])) {
					throw new \Exception('No API endpoint found.'.(!empty($metadata['error_description']) ? ' '.$metadata['error'].': '.$metadata['error_description'] : ''));
				}
				$this->api_endpoint = $metadata['api_endpoint'];
				$this->dc = $metadata['dc'];
				$this->connexion_valide = true; 
			}
		}
		catch (\Exception $e) {
			$error = 'Failed to login to Mailchimp : '.$e->getMessage();
			echo $error . ';';
			$this->logger->error($error);
			return array('error' => $error);
		}
	} // login($paramConnexion)
 
	public function init($paramConnexion) {
		$this->clientid = $paramConnexion['clientid'];
		$this->clientsecret = $paramConnexion['clientsecret'];
		$this->redirect_uri = $paramConnexion['redirect_uri'];	
		if (!empty($paramConnexion['token'])) {
			$this->access_token = $paramConnexion['token'];				
		}
	}
	
	public function getCreateAuthUrl($callbackUrl ) {
		return 'https://login.mailchimp.com/oauth2/authorize?response_type=code&client_id='.$this->clientid.'&redirect_uri='.$this->redirect_uri;
	}
	
	public function testToken(){	
		$result = array();
		try {	
			$this->call('https://login.mailchimp.com/oauth2/authorize?response_type=code&client_id='.$this->clientid.'&redirect_uri='.$this->redirect_uri);				
			$result['error']['code'] = false;
			$result['error']['message'] = false;	
		}
		catch (\Exception $e){
			$result['error']['code'] = $e->getCode();
			$result['error']['message'] = $e->getMessage();
		}
		return $result;				
	}
	
	
	// Get the access token with the code 
	public function setAuthenticate($code) {
		try {	
			$parameters = array(
				'grant_type'	=> 'authorization_code',
				'client_id'		=> $this->clientid,
				'client_secret'	=> $this->clientsecret,
				'redirect_uri'	=> $this->redirect_uri,
				'code'			=> $code
			);	
			$response = $this->call( 'https://login.mailchimp.com/oauth2/token', 'POST', $parameters);
			if (!empty($response['access_token'])) {
				$this->setAccessToken($response['access_token']);
			}
		}
		catch (\Exception $e){
			$response = $e->getMessage();
		}
	}
	
	public function getAccessToken() {
		return 	$this->access_token;
	}
	
	public function setAccessToken($token) {
		$this->access_token = $token;			
	}
 	// Renvoie les modules passés en paramètre
	public function get_modules($type = 'source') {
		try{
			if ($type == 'target') {
				$modules = array(	
									'campaigns' => 'Campaigns',
									'lists' => 'Lists',
									'members' => 'Members'
								);
			}
			else {
				return null;
			}
			return $modules;			
		} catch (\Exception $e) {
			$error = $e->getMessage();
			return $error;			
		}
	} 
	
	// Renvoie les champs du module passé en paramètre
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			// Pour chaque module, traitement différent
			switch ($module) {
				case 'lists':	
					// Si on a le module list en target alors on est en mode search. Il faut juste faire le lien entre le nom de la source et le nom de la liste
					$this->moduleFields = array(
						'name' => array('label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'contact__company' => array('label' => 'Contact - Company', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'contact__address1' => array('label' => 'Contact - Adsress1', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'contact__address2' => array('label' => 'Contact - address2', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'contact__city' => array('label' => 'Contact - city', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'contact__state' => array('label' => 'Contact - state', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'contact__zip' => array('label' => 'Contact - zip', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'contact__country' => array('label' => 'Contact - country', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'contact__phone' => array('label' => 'Contact - phone', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'permission_reminder' => array('label' => 'Permission reminder', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'use_archive_bar' => array('label' => 'Use archive bar', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'campaign_defaults__from_name' => array('label' => 'Campaign defaults from name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'campaign_defaults__from_email' => array('label' => 'Campaign defaults from email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'campaign_defaults__subject' => array('label' => 'Campaign defaults subject', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'campaign_defaults__language' => array('label' => 'Campaign defaults language', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'notify_on_subscribe' => array('label' => 'Notify on subscribe', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'notify_on_unsubscribe' => array('label' => 'Notify on unsubscribe', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'email_type_option' => array('label' => 'Email type option', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 1),
						'visibility' => array('label' => 'Visibility', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					break;
				case 'campaigns':	
					$this->moduleFields = array(
						'settings__title' => array('label' => 'Title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'settings__subject_line' => array('label' => 'Subject', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'settings__from_name' => array('label' => 'From name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'settings__reply_to' => array('label' => 'Reply to', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'settings__use_conversation' => array('label' => 'Use conversation', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'settings__to_name' => array('label' => 'To name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'settings__authenticate' => array('label' => 'Authenticate', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'settings__auto_footer' => array('label' => 'Auto footer', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'settings__inline_css' => array('label' => 'Inline css', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'settings__auto_tweet' => array('label' => 'Auto tweet', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'settings__fb_comments' => array('label' => 'Fb comments', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'variate_settings__winner_criteria' => array('label' => 'Winning Criteria', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('opens' => 'opens','clicks' => 'clicks','manual' => 'manual','total_revenue' => 'total_revenue')),
						'variate_settings__wait_time' => array('label' => 'Wait_time', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'variate_settings__test_size' => array('label' => 'Test size', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'variate_settings__test_size' => array('label' => 'Test size', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'tracking__opens' => array('label' => 'Opens', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'tracking__html_clicks' => array('label' => 'HTML Click Tracking', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'tracking__text_clicks' => array('label' => 'Plain-Text Click Tracking', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'tracking__goal_tracking' => array('label' => 'MailChimp Goal Tracking', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'tracking__ecomm360' => array('label' => 'E-commerce Tracking', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'tracking__google_analytics' => array('label' => 'Google Analytics Tracking', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'tracking__clicktale' => array('label' => 'ClickTale Analytics Tracking', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'rss_opts__feed_url' => array('label' => 'Feed URL', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'rss_opts__frequency' => array('label' => 'Frequency', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('daily' => 'daily','weekly' => 'weekly','monthly' => 'monthly')),
						'rss_opts__constrain_rss_img' => array('label' => 'Constrain RSS Images', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'social_card__title' => array('label' => 'Social card title', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'social_card__description' => array('label' => 'Social card description', 'type' => 'text', 'type_bdd' => 'text', 'required' => 0),
						'social_card__image_url' => array('label' => 'Social card image_url', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'type' => array('label' => 'Campaign Type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => array('regular' => 'regular','plaintext' => 'plaintext','absplit' => 'absplit','rss' => 'rss','variate' => 'variate'))
					);
					$this->fieldsRelate = array(
						'recipients__list_id' => array('label' => 'List ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1)
					);
					break;	
				case 'members':	
					$this->moduleFields = array(
						'email_address' => array('label' => 'Email address', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1),
						'email_type' => array('label' => 'Email Type', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'option' => array('text' => 'text','html' => 'html')),
						'status' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'option' => array('pending' => 'pending','subscribed' => 'subscribed','unsubscribed' => 'unsubscribed','cleaned' => 'cleaned')),
						'language' => array('label' => 'Language', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'vip' => array('label' => 'VIP', 'type' => 'bool', 'type_bdd' => 'tinyint(1)', 'required' => 0),
						'location__latitude' => array('label' => 'Latitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'location__longitude' => array('label' => 'Longitude', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ip_signup' => array('label' => 'Ip signup', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timestamp_signup' => array('Timestamp signup' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'ip_opt' => array('label' => 'Ip opt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'timestamp_opt' => array('label' => 'Timestamp opt', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'merge_fields__FNAME' => array('label' => 'MERGE0', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
						'merge_fields__LNAME' => array('label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
					);
					$this->fieldsRelate = array(
						'list_id' => array('label' => 'List ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0, 'required_relationship' => 1)
					);	
					break;	
				default:
					throw new \Exception("Fields unreadable");
					break;
			}
			// Ajout des champ relate au mapping des champs 
			if (!empty($this->fieldsRelate)) {
				$this->moduleFields = array_merge($this->moduleFields, $this->fieldsRelate);
			}
			// Add list of list in the field list_id
			if (!empty($this->moduleFields['list_id'])) {
				$dataMailchimp['count'] = 100;
				$mailchimpLists = $this->call($this->api_endpoint.'/3.0/lists?count=100','GET');
				if (!empty($mailchimpLists['lists'])) {
					foreach($mailchimpLists['lists'] as $mailchimpList) {
						$this->moduleFields['list_id']['option'][$mailchimpList['id']] = $mailchimpList['name'];
					}
				}			
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module)	 
		
	
	// Permet de créer des données
	public function create($param) {
		// Get module fields to check if the fiels is a boolean
		$this->get_module_fields($param['module'], 'target');
		
		// Tranform Myddleware data to Mailchimp data
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$dataMailchimp = array();
				foreach ($data as $key => $value) {
					// We jump the filed target_id for creation
					if ($key == 'target_id') {
						continue;
					}
					// Transform data, for example for the type boolean : from 1 to true and from 0 to false
					$value = $this->transformValueType($key, $value);
					
					// Formattage des données à envoyer
					$filedStructure = explode('__',$key);
					if (!empty($filedStructure[1])) {
						$dataMailchimp[$filedStructure[0]][$filedStructure[1]] = $value;
					}
					elseif (!empty($filedStructure[0])) { 
						$dataMailchimp[$filedStructure[0]] = $value;
					}
					else {
						throw new \Exception('Field '.$filedStructure.' invalid');
					}
				}

				// Creation du Mailchimp
				
				$urlParam = $this->createUrlParam($param,$data);
				$resultMailchimp = $this->call($this->api_endpoint.'/3.0/'.$urlParam,'POST',$dataMailchimp);				

				// Error management
				if (
						!empty($resultMailchimp['status'])
					&& $resultMailchimp['status'] >= 400
				) {
					$errorMsg = '';
					if (!empty($resultMailchimp['errors'])) {		
						foreach ($resultMailchimp['errors']  as $error) {
							$errorMsg .= print_r($error,true).' ';
						}
					}
					throw new \Exception((!empty($resultMailchimp['title']) ? $resultMailchimp['title'] : 'Error').' ('.$resultMailchimp['status'].'): '.(!empty($resultMailchimp['detail']) ? $resultMailchimp['detail'] : '').(!empty($errorMsg) ? ' => '.$errorMsg : ''));
				}

				if (!empty($resultMailchimp['id'])) {
					$result[$idDoc] = array(
											'id' => $resultMailchimp['id'],
											'error' => false
									);
				}
				else  {
					throw new \Exception("Error webservice. There is no ID in the result of the function $param[module]. ");
				}				
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);		
			}
			// Change the transfer status in Myddleware
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		return $result;
	}
	
	// Permet de créer des données
	public function update($param) {
		// Get module fields to check if the fiels is a boolean
		$this->get_module_fields($param['module'], 'target');
		
		// Tranform Myddleware data to Mailchimp data
		foreach($param['data'] as $idDoc => $data) {
			try {
				// Check control before create
				$data = $this->checkDataBeforeCreate($param, $data);
				$dataMailchimp = array();
				foreach ($data as $key => $value) {
					// We jump the filed target_id
					if ($key == 'target_id') {
						continue;
					}
					// Transform data, for example for the type boolean : from 1 to true and from 0 to false
					$value = $this->transformValueType($key, $value);
					
					// Formattage des données à envoyer
					$filedStructure = explode('__',$key);
					if (!empty($filedStructure[1])) {
						$dataMailchimp[$filedStructure[0]][$filedStructure[1]] = $value;
					}
					elseif (!empty($filedStructure[0])) { 
						$dataMailchimp[$filedStructure[0]] = $value;
					}
					else {
						throw new \Exception('Field '.$filedStructure.' invalid');
					}
				}
				// Creation du Mailchimp
				$urlParam = $this->createUrlParam($param,$data);
				$resultMailchimp = $this->call($this->api_endpoint.'/3.0/'.$urlParam.'/'.$data['target_id'],'PATCH',$dataMailchimp);				

				// Error management
				if (
						!empty($resultMailchimp['status'])
					&& $resultMailchimp['status'] >= 400
				) {
					$errorMsg = '';
					if (!empty($resultMailchimp['errors'])) {		
						foreach ($resultMailchimp['errors']  as $error) {
							$errorMsg .= print_r($error,true).' ';
						}
					}
					throw new \Exception((!empty($resultMailchimp['title']) ? $resultMailchimp['title'] : 'Error').' ('.$resultMailchimp['status'].'): '.(!empty($resultMailchimp['detail']) ? $resultMailchimp['detail'] : '').(!empty($errorMsg) ? ' => '.$errorMsg : ''));
				}


				if (!empty($resultMailchimp['id'])) {
					$result[$idDoc] = array(
											'id' => $resultMailchimp['id'],
											'error' => false
									);
				}
				else  {
					throw new \Exception("Error webservice. There is no ID in the result of the function $param[module]. ");
				}				
			}
			catch (\Exception $e) {
				$error = $e->getMessage();
				$result[$idDoc] = array(
						'id' => '-1',
						'error' => $error
				);		
			}
			// Change the transfer status in Myddleware
			$this->updateDocumentStatus($idDoc,$result[$idDoc],$param);	
		}
		return $result;
	}
	
	// Transform data, for example for the type boolean : from 1 to true and from 0 to false
	protected function transformValueType($key, $value) {
		if (
				!empty($this->moduleFields[$key]['type'])
			&& $this->moduleFields[$key]['type'] == 'bool'
		) {
			if (!empty($value)) {
				return true;
			}
			else {
				return false;
			}
		}
		return $value;
	}
	
	// Create the url parameters depending the module
	protected function createUrlParam($param,$data) {
		if ($param['module'] == 'members') {
			if (empty($data['list_id'])) {
				throw new \Exception('No list id in the data transfer. Failed to create member.');
			}
			else {
				return 'lists/'.$data['list_id'].'/'.$param['module'];
			}
		}	
		return $param['module'];
	}
	
     /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $method The API method to be called
     * @param  array  $args   Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */   
    protected function call($url, $method = 'GET', $args=array(), $timeout = 10){   
	 if (function_exists('curl_init') && function_exists('curl_setopt')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json\r\n'));  
            curl_setopt($ch, CURLOPT_USERAGENT, 'oauth2-draft-v10');
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			if (!empty($this->access_token) and empty($args['oauth_token'])) {
				curl_setopt($ch, CURLOPT_USERPWD, "user:".$this->access_token.'-'.$this->dc);
            }
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

			// For metadata and authentificate call only
			if (!empty($args['oauth_token']) || !empty($args['grant_type'])) {
				$value = http_build_query($args); //params is an array	
				curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
			}
            elseif (!empty($args)) {
                $jsonData = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            
            return $result ? json_decode($result, true) : false;
        }
        throw new \Exception('curl extension is missing!');
    }	
	 
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/mailchimp.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class mailchimp extends mailchimpcore {
		
	}
}