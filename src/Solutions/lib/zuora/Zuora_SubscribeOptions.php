<?php

namespace App\Solutions\lib\zuora;

class Zuora_SubscribeOptions extends Zuora_Object
{
    public const TYPE_NAMESPACE = 'http://api.zuora.com/';

    protected $zType = 'SubscribeOptions';

    protected $zGenerateInvoice;
    protected $zProcessPayments;

    public function __construct(
        $zGenerateInvoice,
        $zProcessPayments
    ) {
        $this->zGenerateInvoice = $zGenerateInvoice;
        $this->zProcessPayments = $zProcessPayments;
    }

    public function getSoapVar()
    {
        return new SoapVar(
            [
                'GenerateInvoice' => $this->zGenerateInvoice,
                'ProcessPayments' => $this->zProcessPayments,
            ],
            SOAP_ENC_OBJECT,
            $this->zType,
            self::TYPE_NAMESPACE
        );
    }
}
