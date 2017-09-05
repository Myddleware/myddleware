<?php
class Zuora_RatePlanChargeData extends Zuora_Object
{
    const TYPE_NAMESPACE = 'http://api.zuora.com/';
    
    protected $zType = 'RatePlanChargeData';
    
    /**
     * @var Zuora_RatePlanCharge
     */
    public $zRatePlanCharge;
    
    /**
     * @var array
     */
    private $_ratePlanChargeTierObjects = array();
    
    public function __construct(Zuora_RatePlanCharge  $zRatePlanCharge = null)
    {
        if (isset($zRatePlanCharge)) {
            $this->zRatePlanCharge = $zRatePlanCharge;
        } else {
            $this->zRatePlanCharge = new Zuora_RatePlanCharge();
        }
    }
    
    public function addRatePlanChargeTier(Zuora_RatePlanChargeTier $zRatePlanChargeTier)
    {
        $this->_ratePlanChargeTierObjects[] = $zRatePlanChargeTier;
    }
    
    public function getSoapVar()
    {
        $ratePlanChargeTierObjects = array();
        foreach ($this->_ratePlanChargeTierObjects as $object) {
            $ratePlanChargeTierObjects[] = $object->getSoapVar();
        }
        return new SoapVar(
            array(
                'RatePlanCharge'=>$this->zRatePlanCharge->getSoapVar(),
                'RatePlanChargeTier'=>$ratePlanChargeTierObjects,
            ),
            SOAP_ENC_OBJECT,
            $this->zType,
            self::TYPE_NAMESPACE
        );
    }
}
