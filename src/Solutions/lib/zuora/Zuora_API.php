<?php

/**
 *    Copyright (c) 2010 Zuora, Inc.
 *
 *    Permission is hereby granted, free of charge, to any person obtaining a copy of
 *    this software and associated documentation files (the "Software"), to use copy,
 *    modify, merge, publish the Software and to distribute, and sublicense copies of
 *    the Software, provided no fee is charged for the Software.  In addition the
 *    rights specified above are conditioned upon the following:
 *
 *    The above copyright notice and this permission notice shall be included in all
 *    copies or substantial portions of the Software.
 *
 *    Zuora, Inc. or any other trademarks of Zuora, Inc.  may not be used to endorse
 *    or promote products derived from this Software without specific prior written
 *    permission from Zuora, Inc.
 *
 *    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *    FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
 *    ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
 *    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *    ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace App\Solutions\lib\zuora;

/**
 * Zuora PHP Library.
 *
 * This class implements singleton pattern and allows user to call
 * any of Zuora's API Calls except for login which will be called
 * automatically prior to any other call
 */
class Zuora_API
{
    /**
     * Singleton instance.
     *
     * Marked only as protected to allow extension of the class. To extend,
     * simply override {@link getInstance()}.
     *
     * @var Zuora_API
     */
    protected static $_instance;

    protected static $_config;

    /**
     * Soap Client.
     *
     * @var SoapClient
     */
    protected $_client;

    /**
     * @var SoapHeader
     */
    protected $_header;

    protected $_endpoint;

    protected static $_classmap = [
        'zObject' => 'Zuora_Object',
        'Account' => 'Zuora_Account',
        'InvoiceAdjustment' => 'Zuora_InvoiceAdjustment',
        'InvoiceItemAdjustment' => 'Zuora_InvoiceItemAdjustment',
        'Amendment' => 'Zuora_Amendment',
        'Contact' => 'Zuora_Contact',
        'Invoice' => 'Zuora_Invoice',
        'Refund' => 'Zuora_Refund',
        'RefundInvoicePayment' => 'Zuora_RefundInvoicePayment',
        'InvoiceItem' => 'Zuora_InvoiceItem',
        'InvoicePayment' => 'Zuora_InvoicePayment',
        'Payment' => 'Zuora_Payment',
        'PaymentMethod' => 'Zuora_PaymentMethod',
        'Product' => 'Zuora_Product',
        'ProductRatePlan' => 'Zuora_ProductRatePlan',
        'ProductRatePlanCharge' => 'Zuora_ProductRatePlanCharge',
        'ProductRatePlanChargeTier' => 'Zuora_ProductRatePlanChargeTier',
        'RatePlan' => 'Zuora_RatePlan',
        'RatePlanCharge' => 'Zuora_RatePlanCharge',
        'RatePlanChargeTier' => 'Zuora_RatePlanChargeTier',
        'Subscription' => 'Zuora_Subscription',
        'Usage' => 'Zuora_Usage',
        'Export' => 'Zuora_Export',
        'ID' => 'Zuora_ID',
        'SubscribeRequest' => 'Zuora_SubscribeRequest',
        'SubscribeOptions' => 'Zuora_SubscribeOptions',
        'SubscriptionData' => 'Zuora_SubscriptionData',
        'RatePlanData' => 'Zuora_RatePlanData',
        'RatePlanChargeData' => 'Zuora_RatePlanChargeData',
        'ProductRatePlanChargeTierData' => 'Zuora_ProductRatePlanChargeTierData',
        'InvoiceData' => 'Zuora_InvoiceData',
        'PreviewOptions' => 'Zuora_PreviewOptions',
        'SubscribeResult' => 'Zuora_SubscribeResult',
        'QueryLocator' => 'Zuora_QueryLocator',
        'Error' => 'Zuora_Error',
        'ErrorCode' => 'Zuora_ErrorCode',
        'SessionHeader' => 'Zuora_SessionHeader',
        'DummyHeader' => 'Zuora_DummyHeader',
        'ApiFault' => 'Zuora_ApiFault',
        'LoginFault' => 'Zuora_LoginFault',
        'InvalidTypeFault' => 'Zuora_InvalidTypeFault',
        'InvalidValueFault' => 'Zuora_InvalidValueFault',
        'MalformedQueryFault' => 'Zuora_MalformedQueryFault',
        'InvalidQueryLocatorFault' => 'Zuora_InvalidQueryLocatorFault',
        'UnexpectedErrorFault' => 'Zuora_UnexpectedErrorFault',
    ];

    /**
     * Instantiate using {@link getInstance()}; API is a singleton
     * object.
     *
     * @return void
     *
     * @param mixed $config
     */
    protected function __construct($config)
    {
        self::$_config = $config;

        $this->_client = new SoapClient(self::$_config->wsdl,
            [
                'soap_version' => SOAP_1_1,
                'trace' => 1,
                'classmap' => self::$_classmap,
            ]
        );
    }

    /**
     * Log in to Zuora and create a session.
     *
     * @return bool
     *
     * @throws \ZuoraFault\ZuoraFault
     *
     * @param mixed $username
     * @param mixed $password
     */
    public function login($username, $password)
    {
        if ($this->_endpoint) {
            $this->setLocation($this->_endpoint);
        }
        try {
            $result = $this->_client->login(['username' => $username, 'password' => $password]);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }
        $header = new SoapHeader(
            'http://api.zuora.com/',
            'SessionHeader',
            [
                'session' => $result->result->Session,
            ]
        );

        $this->addHeader($header);
        $this->_client->__setLocation($result->result->ServerUrl);

        return true;
    }

    public function clearHeaders()
    {
        $this->_header = null;
    }

    public function addHeader($hdr)
    {
        if (!$this->_header) {
            $this->_header = [];
        }
        $this->_header[] = $hdr;
    }

    public function setQueryOptions($batchSize)
    {
        $header = new SoapHeader(
            'http://api.zuora.com/',
            'QueryOptions',
            [
                'batchSize' => $batchSize,
            ]
        );
        $this->addHeader($header);
    }

    public function setQueueHeader($resultEmail, $userId)
    {
        $header = new SoapHeader(
            'http://api.zuora.com/',
            'QueueHeader',
            [
                'resultEmail' => $resultEmail,
                'userId' => $userId,
            ]
        );
        $this->addHeader($header);
    }

    public function setLocation($endpoint)
    {
        $this->_endpoint = $endpoint;
        $this->_client->__setLocation($this->_endpoint);
    }

    /**
     * Execute subscribe() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     */
    public function subscribe(
        Zuora_Account $zAccount,
        Zuora_SubscriptionData $zSubscriptionData,
        Zuora_Contact $zBillToContact = null,
        Zuora_PaymentMethod $zPaymentMethod = null,
        Zuora_SubscribeOptions $zSubscribeOptions = null,
        Zuora_Contact $zSoldToContact = null
    ) {
        $subscribeRequest = [
            'Account' => $zAccount->getSoapVar(),
            'SubscriptionData' => $zSubscriptionData->getSoapVar(),
        ];

        // Optional variables
        foreach (['BillToContact', 'PaymentMethod', 'SoldToContact', 'SubscribeOptions'] as $var) {
            $localVarName = "z{$var}";
            if (isset($$localVarName)) {
                $subscribeRequest[$var] = $$localVarName->getSoapVar();
            }
        }

        try {
            $result = $this->_client->__soapCall('subscribe', ['zObjects' => [$subscribeRequest]], null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute subscribeWithExistingAccount() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     */
    public function subscribeWithExistingAccount(
        Zuora_Account $zAccount,
        Zuora_SubscriptionData $zSubscriptionData,
        Zuora_SubscribeOptions $zSubscribeOptions = null
    ) {
        $subscribeRequest = [
            'Account' => $zAccount->getSoapVar(),
            'SubscriptionData' => $zSubscriptionData->getSoapVar(),
        ];

        if (isset($zSubscribeOptions)) {
            $subscribeRequest['SubscribeOptions'] = $zSubscribeOptions->getSoapVar();
        }

        try {
            $result = $this->_client->__soapCall('subscribe', ['zObjects' => [$subscribeRequest]], null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute create() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     */
    public function create(array $zObjects)
    {
        if (count($zObjects) > 50) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__.': only supports up to 50 objects');
        }
        $soapVars = [];
        $type = 'Zuora_Object';
        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__.': all objects must be of the same type');
            }
        }
        $create = [
            'zObjects' => $soapVars,
        ];
        try {
            $result = $this->_client->__soapCall('create', $create, null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Amend() call.
     *
     * @param $zAmendment Amendment to be created
     * @param $zAmendOptions Override of default amendment options
     * @param $zPreviewOptions Override of default preview options
     *
     * @return AmendResults
     */
    public function amend($zAmendment, $zAmendOptions, $zPreviewOptions)
    {
        // Set up Default amend options and preview options
        if (null == $zAmendOptions) {
            $zAmendOptions = [
                'GenerateInvoice' => false,
                'ProcessPayments' => false,
            ];
        }
        if (null == $zPreviewOptions) {
            $zPreviewOptions = [
                'EnablePreviewMode' => false,
                'NumberOfPeriods' => 1,
            ];
        }
        // construct amend components
        $amendRequest = [
            'Amendments' => $zAmendment,
            'AmendOptions' => $zAmendOptions,
            'PreviewOptions' => $zPreviewOptions,
        ];
        $amendWrapper = ['requests' => $amendRequest];
        $amendWrapper = ['amend' => $amendWrapper];

        try {
            // Make amend request
            $amendResult = $this->_client->__soapCall('amend', $amendWrapper, null, $this->_header);

            return $amendResult;
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }
    }

    /**
     * Execute generate() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     */
    public function generate(array $zObjects)
    {
        if (count($zObjects) > 50) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__.': only supports up to 50 objects');
        }
        $soapVars = [];
        $type = 'Zuora_Object';
        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__.': all objects must be of the same type');
            }
        }
        $generate = [
            'zObjects' => $soapVars,
        ];
        try {
            $result = $this->_client->__soapCall('generate', $generate, null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute update() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     */
    public function update(array $zObjects)
    {
        if (count($zObjects) > 50) {
            ZuoraFault('ERROR in '.__METHOD__.': only supports up to 50 objects');
        }
        $soapVars = [];
        $type = 'Zuora_Object';
        foreach ($zObjects as $zObject) {
            if ($zObject instanceof $type) {
                $type = get_class($zObject);
                $soapVars[] = $zObject->getSoapVar();
            } else {
                throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__.': all objects must be of the same type');
            }
        }
        $update = [
            'zObjects' => $soapVars,
        ];
        try {
            $result = $this->_client->__soapCall('update', $update, null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute delete() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     *
     * @param mixed $type
     * @param mixed $ids
     */
    public function delete($type, $ids)
    {
        $delete = [
            'type' => $type,
            'ids' => $ids,
        ];
        $deleteWrapper = [
            'delete' => $delete,
        ];

        try {
            $result = $this->_client->__soapCall('delete', $deleteWrapper, null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute executet() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     *
     * @param mixed $type
     * @param mixed $syncronous
     * @param mixed $ids
     */
    public function execute($type, $syncronous, $ids)
    {
        $execute = [
            'type' => $type,
            'synchronous' => $syncronous,
            'ids' => $ids,
        ];
        $executeWrapper = [
            'execute' => $execute,
        ];

        try {
            $result = $this->_client->__soapCall('execute', $executeWrapper, null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute getUserInfo() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     */
    public function getUserInfo()
    {
        try {
            $result = $this->_client->__soapCall('getUserInfo', [], null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute query() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     *
     * @param mixed $zoql
     */
    public function query($zoql)
    {
        $query = [
            'queryString' => $zoql,
        ];
        $queryWrapper = [
            'query' => $query,
        ];

        try {
            $result = $this->_client->__soapCall('query', $queryWrapper, null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Execute queryMore() API call.
     *
     * @return result object
     *
     * @throws \ZuoraFault\ZuoraFault
     *
     * @param mixed $zoql
     */
    public function queryMore($zoql)
    {
        $query = [
            'queryLocator' => $zoql,
        ];
        $queryWrapper = [
            'queryMore' => $query,
        ];

        try {
            $result = $this->_client->__soapCall('queryMore', $queryWrapper, null, $this->_header);
        } catch (SoapFault $e) {
            throw new ZuoraFault\ZuoraFault('ERROR in '.__METHOD__, $e, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }

        return $result;
    }

    /**
     * Singleton instance.
     *
     * @return Zuora_API
     *
     * @param mixed $config
     */
    public static function getInstance($config)
    {
        if (null === self::$_instance || $config != self::$_config) {
            self::$_instance = new self($config);
        }

        return self::$_instance;
    }
}
