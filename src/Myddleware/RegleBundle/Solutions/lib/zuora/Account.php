<?php
class Zuora_Account extends Zuora_Object
{
    protected $zType = 'Account';
    
    public function __construct()
    {
        $this->_data = array(
            'AccountNumber'=>null,
        );
    }
}
