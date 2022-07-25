<?php

namespace App\Form\DataTransformer;

use Illuminate\Encryption\Encrypter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\DataTransformerInterface;

class ConnectorParamsValueTransformer implements DataTransformerInterface
{
    private string $secret;
    private Encrypter $encrypter;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
        $this->encrypter = new Encrypter(substr($this->secret, -16));
    }

    public function reverseTransform($value): mixed
    {
        $value->setValue($this->encrypter->encrypt($value->getValue()));

        return $value;
    }

    public function transform($value): mixed
    {
        if (!$value) {
            return null;
        }

        $value->setValue($this->decryptParameters($value->getValue()));

        return $value;
    }

    // Decrypts login fields for a solution
    private function decryptParameters($parameters): string|array|null
    {
        if (is_array($parameters)) {
            $return_params = [];
            foreach ($parameters as $key => $value) {
                if (is_string($value)) {
                    $return_params[$key] = $this->encrypter->decrypt($value);
                }
            }

            return $return_params;
        }

        return null != $parameters ? $this->encrypter->decrypt($parameters) : null;
    }
}
