<?php

namespace Myddleware\RegleBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Illuminate\Encryption\Encrypter;


class ConnectorParamsValueTransformer implements DataTransformerInterface {

    private $_secret = null;

    public function __construct($secret) {
        $this->_secret = $secret;
    }

    public function reverseTransform($value) {
        // Generate object to encrypt data
        $encrypter = new Encrypter(substr($this->_secret, -16));
        
        $string = $value->getValue();
        $value->setValue($encrypter->encrypt($value->getValue()));  
        
        return $value;
    }

    public function transform($value) {
        
        $value->setValue($this->decrypt_params($value->getValue()));
        
        return $value;
    }

    // Décrypte les paramètres de connexion d'une solution
    private function decrypt_params($tab_params) {
        // Instanciate object to decrypte data
        $encrypter = new Encrypter(substr($this->_secret, -16));
        if (is_array($tab_params)) {
            $return_params = array();
            foreach ($tab_params as $key => $value) {
                if (is_string($value)) {
                    $return_params[$key] = $encrypter->decrypt($value);
                }
            }
            return $return_params;
        } else {
            return $tab_params !=null ? $encrypter->decrypt($tab_params) : null;
        }
    }

}
