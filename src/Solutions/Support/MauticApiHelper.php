<?php

namespace App\Solutions\Support;

use App\Solutions\Http\ConnectorHttpClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MauticApiHelper
{
    public function __construct(
        private readonly ConnectorHttpClientFactory $httpClientFactory = new ConnectorHttpClientFactory(),
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function call(string $url, string $method, mixed $data, array $requestHeaders, LoggerInterface $logger): array
    {
        $httpMethod = strtoupper($method);
        $requestOptions = [
            'headers' => $requestHeaders,
            'timeout' => 60,
        ];

        $jsonPayload = $this->prepareJsonPayload($data, $httpMethod);
        if (null !== $jsonPayload) {
            $requestOptions['body'] = $jsonPayload;
        }

        $response = $this->httpClientFactory->create()->request($httpMethod, $url, $requestOptions);

        return $this->parseApiResponse($response->getContent(false), $response->getStatusCode(), $httpMethod, $url, $logger);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getOAuth2AccessToken(
        string $baseUrl,
        string $clientId,
        string $clientSecret,
        int $currentTimestamp,
        ?string $accessToken,
        int $tokenExpiresAt,
        LoggerInterface $logger,
    ): array {
        if (!empty($accessToken) && $tokenExpiresAt > ($currentTimestamp + 60)) {
            return [
                'access_token' => $accessToken,
                'token_expires_at' => $tokenExpiresAt,
            ];
        }

        $response = $this->httpClientFactory->create()->request('POST', $baseUrl.'/oauth/v2/token', [
            'timeout' => 60,
            'headers' => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]),
        ]);

        $statusCode = $response->getStatusCode();
        $decodedResponse = json_decode($response->getContent(false), true);

        if (!is_array($decodedResponse)) {
            throw new \Exception('OAuth token response was not JSON.');
        }

        if ($statusCode >= 400) {
            $message = $decodedResponse['error_description'] ?? ('HTTP '.$statusCode);
            $logger->error('Mautic OAuth token error', ['status_code' => $statusCode, 'message' => $message]);
            throw new \Exception('Mautic OAuth token request failed.');
        }

        if (empty($decodedResponse['access_token']) || empty($decodedResponse['expires_in'])) {
            throw new \Exception('OAuth token response missing access_token/expires_in.');
        }

        return [
            'access_token' => (string) $decodedResponse['access_token'],
            'token_expires_at' => $currentTimestamp + (int) $decodedResponse['expires_in'],
        ];
    }

    public function buildRequestHeaders(?string $authHeader): array
    {
        $requestHeaders = ['Accept: application/json'];

        if (!empty($authHeader)) {
            $requestHeaders[] = $authHeader;
        }

        return $requestHeaders;
    }

    public function buildWriteErrorResult(\Exception $exception, string $documentId, array $param, array $payloadData): array
    {
        return [
            'log_context' => [
                'method' => 'buildWriteErrorResult',
                'document_id' => $documentId,
                'module' => $param['module'] ?? null,
                'payload' => $payloadData,
                'error' => $exception->getMessage(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
            ],
            'result' => [
                'id' => '-1',
                'error' => $exception->getMessage(),
            ],
        ];
    }

    private function prepareJsonPayload(mixed $data, string $httpMethod): ?string
    {
        $methodsWithBody = ['POST', 'PUT', 'PATCH'];

        if (empty($data) || !in_array($httpMethod, $methodsWithBody, true)) {
            return null;
        }

        return json_encode($data);
    }

    private function parseApiResponse(string $rawResponse, int $statusCode, string $httpMethod, string $requestUrl, LoggerInterface $logger): array
    {
        $trimmedResponse = trim($rawResponse);

        if ('' === $trimmedResponse) {
            return $this->parseEmptyApiResponse($statusCode, $logger);
        }

        $decodedResponse = json_decode($rawResponse, true);
        if (!is_array($decodedResponse)) {
            return $this->parseNonJsonApiResponse($rawResponse, $statusCode, $logger);
        }

        if ($statusCode >= 400) {
            $this->throwApiErrorException($decodedResponse, $rawResponse, $statusCode, $httpMethod, $requestUrl, $logger);
        }

        return $decodedResponse;
    }

    private function parseEmptyApiResponse(int $statusCode, LoggerInterface $logger): array
    {
        if ($statusCode >= 400) {
            $logger->error('Mautic HTTP error', ['status_code' => $statusCode]);
            throw new \Exception('Mautic HTTP request failed.');
        }

        return [];
    }

    private function parseNonJsonApiResponse(string $rawResponse, int $statusCode, LoggerInterface $logger): array
    {
        if ($statusCode >= 400) {
            $logger->error('Mautic non-JSON HTTP error', ['status_code' => $statusCode, 'raw_response' => $rawResponse]);
            throw new \Exception('Mautic HTTP request failed with a non-JSON response.');
        }

        return ['raw' => $rawResponse];
    }

    private function throwApiErrorException(
        array $decodedResponse,
        string $rawResponse,
        int $statusCode,
        string $httpMethod,
        string $requestUrl,
        LoggerInterface $logger,
    ): void {
        $errorMessage = $decodedResponse['error_description']
            ?? ($decodedResponse['error']['message'] ?? null)
            ?? ('HTTP '.$statusCode);

        $logger->error('Mautic API error', [
            'status_code' => $statusCode,
            'message' => $errorMessage,
            'http_method' => $httpMethod,
            'request_url' => $requestUrl,
            'response_detail' => $this->buildApiErrorDetail($decodedResponse, $rawResponse),
        ]);

        throw new \Exception('Mautic API request failed.');
    }

    private function buildApiErrorDetail(array $decodedResponse, string $rawResponse): string
    {
        $responseDetail = !empty($decodedResponse)
            ? json_encode($decodedResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : $rawResponse;

        if (is_string($responseDetail) && strlen($responseDetail) > 2000) {
            return substr($responseDetail, 0, 2000).'…';
        }

        return (string) $responseDetail;
    }
}
