<?php

namespace App\Solutions\lib\zuora;

class Zuora_ProductRatePlanChargeTier extends Zuora_Object
{
    protected $zType = 'ProductRatePlanChargeTier';

    public function __construct()
    {
        $this->_data = [
            'ProductRatePlanTierId' => null,
        ];
    }
}
