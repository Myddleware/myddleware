<?php

namespace App\Solutions\Client;

class VtigerClient
{
    private string $url;
    private string $sessionName = '';

    public function __construct(string $baseUrl)
    {
        $this->url = rtrim($baseUrl, '/') . '/webservice.php';
    }

    public function getSessionName(): string
    {
        return $this->sessionName;
    }

    public function login(string $username, string $accessKey): array
    {
        $challenge = $this->get(['operation' => 'getchallenge', 'username' => $username]);
        if (!$challenge['success']) {
            return $challenge;
        }

        $token = $challenge['result']['token'];
        $generatedKey = md5($token . $accessKey);

        $result = $this->post([
            'operation' => 'login',
            'username' => $username,
            'accessKey' => $generatedKey,
        ]);

        if ($result['success']) {
            $this->sessionName = $result['result']['sessionName'];
        }

        return $result;
    }

    public function logout(): array
    {
        return $this->post(['operation' => 'logout', 'sessionName' => $this->sessionName]);
    }

    public function listTypes(): array
    {
        return $this->get(['operation' => 'listtypes', 'sessionName' => $this->sessionName]);
    }

    public function describe(string $elementType): array
    {
        return $this->get(['operation' => 'describe', 'sessionName' => $this->sessionName, 'elementType' => $elementType]);
    }

    public function query(string $query): array
    {
        return $this->get(['operation' => 'query', 'sessionName' => $this->sessionName, 'query' => $query]);
    }

    public function retrieve(string $id): array
    {
        return $this->get(['operation' => 'retrieve', 'sessionName' => $this->sessionName, 'id' => $id]);
    }

    public function create(string $elementType, array $data): array
    {
        return $this->post([
            'operation' => 'create',
            'sessionName' => $this->sessionName,
            'elementType' => $elementType,
            'element' => json_encode($data),
        ]);
    }

    public function update(string $elementType, array $data): array
    {
        return $this->post([
            'operation' => 'update',
            'sessionName' => $this->sessionName,
            'element' => json_encode($data),
        ]);
    }

    public function delete(string $id): array
    {
        return $this->post([
            'operation' => 'delete',
            'sessionName' => $this->sessionName,
            'id' => $id,
        ]);
    }

    private function get(array $params): array
    {
        $url = $this->url . '?' . http_build_query($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?: ['success' => false, 'error' => ['message' => 'Invalid response']];
    }

    private function post(array $params): array
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?: ['success' => false, 'error' => ['message' => 'Invalid response']];
    }
}
