<?php

namespace App\Solutions\lib\zuora;

use Exception;
use SoapFault;

class ZuoraFault extends Exception
{
    protected $previous;

    public function __construct($message = '', SoapFault $previous = null, $request_headers = '', $last_request = '', $response_headers = '', $last_response = '')
    {
        $this->request_headers = $request_headers;
        $this->last_request = $last_request;
        $this->response_headers = $response_headers;
        $this->last_response = $last_response;
        $this->previous = $previous;
        parent::__construct($message);
    }

    public function __toString()
    {
        $message = $this->getMessage().' in '.$this->getFile().':'.$this->getLine()."\n";
        if ($this->previous) {
            $message .= $this->previous->faultstring."\n";
        }

        return $message;
    }

    /**
     * Similar to the PHP 5.3 Exception API.
     */
    public function getPreviousException()
    {
        return $this->previous;
    }
}
