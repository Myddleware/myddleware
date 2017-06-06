<?php
class Zuora_SubscribeRequest extends Zuora_Object
{
    protected $zType = 'SubscribeRequest';
    
    public function __construct(
        Zuora_Account $zAccount,
        Zuora_Contact $zBillTo,
        Zuora_PaymentMethod $zPaymentMethod,
        Zuora_SubscriptionData $zSubscriptionData,
        Zuora_SubscribeOptions $zOptions=null,
        Zuora_Contact $zSoldTo=null
    )
    {
        $this->_data = array(
            'Account'=>$zAccount,
            'BillTo'=>$zBillTo,
            'PaymentMethod'=>$zPaymentMethod,
            'SubscriptionData'=>$zSubscriptionData,
        );
        if (isset($zSoldTo)) {
            $this->_data['SoldToContact'] = $zSoldTo;
        }
        if (isset($zOptions)) {
            $this->_data['SubscribeOptions'] = $zOptions;
        }
    }
}
