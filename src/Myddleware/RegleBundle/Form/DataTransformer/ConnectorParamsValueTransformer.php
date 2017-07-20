<?php

namespace Myddleware\RegleBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ConnectorParamsValueTransformer implements DataTransformerInterface {

    private $_secret = null;
    
    public function __construct($secret) {
        $this->_secret = $secret;
    }
    public function reverseTransform($value) {
        
    }

    public function transform($value) {
        $value->setValue($this->decrypt_params($value->getValue()));
        return $value;
    }

    // Décrypte les paramètres de connexion d'une solution
    private function decrypt_params($tab_params) {
        // Instanciate object to decrypte data
        $encrypter = new \Illuminate\Encryption\Encrypter(substr($this->_secret, -16));
        if (is_array($tab_params)) {
            $return_params = array();
            foreach ($tab_params as $key => $value) {
                if (is_string($value)) {
                    $return_params[$key] = $encrypter->decrypt($value);
                }
            }
            return $return_params;
        } else {
            return $encrypter->decrypt($tab_params);
        }
    }

}
