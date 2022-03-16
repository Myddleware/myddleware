<?php

namespace App\Form\DataTransformer;

use Illuminate\Encryption\Encrypter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\DataTransformerInterface;

class ConnectorParamsValueTransformer implements DataTransformerInterface
{
    private $_secret = null;

    public function __construct(ParameterBagInterface $params)
    {
        $this->_secret = $params->get('secret');
    }

    public function reverseTransform($value): mixed
    {
        // Generate object to encrypt data
        $encrypter = new Encrypter(substr($this->_secret, -16));

        $value->setValue($encrypter->encrypt($value->getValue()));

        return $value;
    }

    public function transform($value): mixed
    {
        $value->setValue($this->decrypt_params($value->getValue()));

        return $value;
    }

    // Décrypte les paramètres de connexion d'une solution
    private function decrypt_params($tab_params)
    {
        // Instanciate object to decrypte data
        $encrypter = new Encrypter(substr($this->_secret, -16));
        if (is_array($tab_params)) {
            $return_params = [];
            foreach ($tab_params as $key => $value) {
                if (is_string($value)) {
                    $return_params[$key] = $encrypter->decrypt($value);
                }
            }

            return $return_params;
        }

        return null != $tab_params ? $encrypter->decrypt($tab_params) : null;
    }
}
