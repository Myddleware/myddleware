<?php

namespace App\Solutions\lib\zuora;

class Zuora_Account extends Zuora_Object
{
    protected $zType = 'Account';

    public function __construct()
    {
        $this->_data = [
            'AccountNumber' => null,
        ];
    }
}
