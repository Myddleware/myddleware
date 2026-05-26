<?php

namespace App\Solutions\Support;

use App\Solutions\Http\ConnectorHttpClientFactory;

class DolibarrApiHelper
{
    public function __construct(
        private readonly ConnectorHttpClientFactory $httpClientFactory = new ConnectorHttpClientFactory(),
    ) {
    }

    public function callApi(callable $buildRequestOptions, callable $normalizeApiResponse, string $url, string $method = 'GET', array $args = [], int $timeout = 60): mixed
    {
        $method = strtoupper($method);
        $requestOptions = $buildRequestOptions($method, $args, $timeout);

        if ('GET' === $method && !empty($args)) {
            $url = sprintf('%s?%s', $url, http_build_query($args));
        }

        $response = $this->httpClientFactory->create()->request($method, $url, $requestOptions);

        return $normalizeApiResponse($response->getStatusCode(), $response->getContent(false));
    }
}
