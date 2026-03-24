<?php

namespace App\Solutions\Http;

use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ConnectorHttpClientFactory
{
    public function create(): HttpClientInterface
    {
        return new NativeHttpClient();
    }
}
