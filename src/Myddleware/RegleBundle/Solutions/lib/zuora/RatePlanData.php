<?php
class Zuora_RatePlanData extends Zuora_Object
{
    const TYPE_NAMESPACE = 'http://api.zuora.com/';
    
    protected $zType = 'RatePlanData';
    
    /**
     * @var Zuora_RatePlan
     */
    public $zRatePlan;
    
    /**
     * @var array
     */
    private $_ratePlanChargeDataObjects = array();
    
    public function __construct(Zuora_RatePlan  $zRatePlan = null)
    {
        if (isset($zRatePlan)) {
            $this->zRatePlan = $zRatePlan;
        } else {
            $this->zRatePlan = new Zuora_RatePlan();
        }
    }
    
    public function addRatePlanChargeData(Zuora_RatePlanChargeData $zRatePlanChargeData)
    {
        $this->_ratePlanChargeDataObjects[] = $zRatePlanChargeData;
    }
    
    public function getSoapVar()
    {
        $ratePlanChargeDataObjects = array();
        foreach ($this->_ratePlanChargeDataObjects as $object) {
            $ratePlanChargeDataObjects[] = $object->getSoapVar();
        }
        return new SoapVar(
            array(
                'RatePlan'=>$this->zRatePlan->getSoapVar(),
                'RatePlanChargeData'=>$ratePlanChargeDataObjects,
            ),
            SOAP_ENC_OBJECT,
            $this->zType,
            self::TYPE_NAMESPACE
        );
    }
}
