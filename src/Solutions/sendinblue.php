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

use PhpParser\Node\Name;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class sendinbluecore extends solution
{
    protected $config;

    
    public function getFieldsLogin()
    {
        return [
            [
                'name' => 'login',
                'type' => TextType::class,
                'label' => 'solution.fields.login',
            ],
            [
                'name' => 'apikey',
                'type' => PasswordType::class,
                'label' => 'solution.fields.apikey',
            ],        
        ];
    }

// connect to Sendinblue
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        // Configure API key authorization: api-key
        $this->config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->paramConnexion['apikey']);

        $apiInstance = new \SendinBlue\Client\Api\AccountApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new \GuzzleHttp\Client(),
            $this->config
        );

        try {
            $result = $apiInstance->getAccount();
            if (!empty($result->getEmail())) {
                $this->connexion_valide = true;
            } else {
                return ['error' => 'Failed to connect to Sendinblue: '. $result->message];
            }
            throw new \Exception(print_r($result->getEmail(), true));

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->logger->error($error);
            
            return ['error' => $error];
        }
        
    }
    
    //Get module list
    public function get_modules($type = 'source')
    {
        if ('source' == $type) {
            return [
               'contacts' => 'contacts'
            ];
        }

        return [
            'contacts' => 'contacts',
        ];
    }

    //Returns the fields of the module passed in parameter
    public function get_module_fields($module, $type = 'source', $param = null)
    {
        parent::get_module_fields($module, $type);
        
        try {
            $apiInstance = new \SendinBlue\Client\Api\AttributesApi( new \GuzzleHttp\Client(), $this->config );
            $results = $apiInstance->getAttributes();
            $attributes = $results->getAttributes();
            echo '<pre>';
           // $this->moduleFields = [];
           
            foreach ($attributes as $attribute) {
       
                $this->moduleFields [$attribute->getName()] = [
                    'label' => $attribute->getName(),
                    'required' => false,
                    'type' => 'varchar(255)', // ? Settare il type giusto?
                    'type_bdd' => 'varchar(255)',
                    'required_relationship' => false,
                    'relate' => false
                ];
                
              //$this->moduleFields = array_merge($this->moduleFields, $attributes);
             
           
            }   
          //  print_r($this->moduleFields);
               // die();
            return $this->moduleFields;     
            
   /*
            if ($module == "contacts") {
                $moduleFiels = [
                    'BLACKLIST'  => ['label' => 'Blacklist', 'type' => 'float', 'type_bdd' => 'float','required' => 0],
                    'READERS'    => ['label' => 'Readers', 'type' => 'float', 'type_bdd' => 'float','required' => 0],
                    'CLICKERS'   => ['label' => 'Clickers', 'type' => 'float', 'type_bdd' => 'float','required' => 0],
                    'NOM'        => ['label' => 'Name', 'type' => 'text', 'type_bdd' => 'varchar(255)','required' => 0],
                    'PRENOM'     => ['label' => 'First name', 'type' => 'text', 'type_bdd' => 'varchar(255)','required' => 0],
                    'SMS'        => ['label' => 'Sms', 'type' => 'text', 'type_bdd' => 'varchar(255)','required' => 0]
                ];                    
                /*echo '<pre>';                
                var_dump($moduleFiels);
                die();*/
               /* if (!empty($moduleFiels)) {
                    foreach ($moduleFiels as $moduleFiel) {
                        print_r($moduleFiels);
                        $label = $moduleFiel['label'];
                        echo $label;
                        die();
                    }
                    
                    die();
                }
            }else{
                echo "no";
                die();
            }*/
            

        } catch (\Exception $e) {
            $error = $e->getMessage();

            return false;
        }

   /*     try {              
        $this->moduleFields = [
            'user_id' => 'user_id',
            'login'   => 'login',
            'email'   => 'email'
        ];

        //$this->paramConnexion.$this->apiInstance
        return $this->moduleFields;
            
        } catch (\Exception $e) {
            $error = $e->getMessage();

            return false;
        }*/
      
   }

}

class sendinblue extends sendinbluecore
{

}

