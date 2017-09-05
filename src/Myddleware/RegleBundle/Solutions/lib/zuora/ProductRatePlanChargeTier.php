<?php
class Zuora_ProductRatePlanChargeTier extends Zuora_Object
{
    protected $zType = 'ProductRatePlanChargeTier';

    public function __construct()
    {
        $this->_data = array(
            'ProductRatePlanTierId'=>null,
        );
    }
}
