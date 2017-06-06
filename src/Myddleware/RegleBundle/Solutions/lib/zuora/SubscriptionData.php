<?php
class Zuora_SubscriptionData extends Zuora_Object
{
    const TYPE_NAMESPACE = 'http://api.zuora.com/';
    
    protected $zType = 'SubscriptionData';
    
    /**
     * @var Zuora_Subscription
     */
    public $zSubscription;
    
    /**
     * @var array
     */
    private $_ratePlanDataObjects = array();
    
    public function __construct(Zuora_Subscription $zSubscription = null)
    {
        if (isset($zSubscription)) {
            $this->zSubscription = $zSubscription;
        } else {
            $this->zSubscription = new Zuora_Subscription();
        }
    }
    
    public function addRatePlanData(Zuora_RatePlanData $zRatePlanData)
    {
        $this->_ratePlanDataObjects[] = $zRatePlanData;
    }
    
    public function getSoapVar()
    {
        foreach ($this->_ratePlanDataObjects as $object) {
            $ratePlanDataObjects[] = $object->getSoapVar();
        }
        return new SoapVar(
            array(
                'Subscription'=>$this->zSubscription->getSoapVar(),
                'RatePlanData'=>$ratePlanDataObjects,
            ),
            SOAP_ENC_OBJECT,
            $this->zType,
            self::TYPE_NAMESPACE
        );
    }
}
