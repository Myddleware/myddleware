<?php
class Zuora_Subscription extends Zuora_Object
{
    protected $zType = 'Subscription';
    
    public function __construct()
    {
        $date = date('Y-m-d\TH:i:s');
        $this->_data = array(
            'AutoRenew'=>1,
            'ContractAcceptanceDate'=>$date,
            'ContractEffectiveDate'=>$date,
            'Currency'=>'USD',
            'InitialTerm'=>12,
            'RenewalTerm'=>12,
            'ServiceActivationDate'=>$date,
            'Status'=>'Active',
            'TermStartDate'=>$date,
            'Version'=>1,
        );
    }
}
