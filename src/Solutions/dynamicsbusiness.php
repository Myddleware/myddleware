<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com

 This file is part of Myddleware.

 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace App\Solutions;

use GuzzleHttp\Client;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class dynamicsbusiness extends solution
{
    protected $token;
    public bool $connexion_valide = false;

    public function login($paramConnexion)
    {
        parent::login($paramConnexion);

        $tenantId = $paramConnexion['tenant_id'];
        $clientId = $paramConnexion['client_id'];
        $clientSecret = $paramConnexion['client_secret'];
        $scope = 'https://api.businesscentral.dynamics.com/.default';
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        $client = new Client();
        $response = $client->post($tokenUrl, [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['access_token'])) {
            $this->token = $data['access_token'];
            $this->connexion_valide = true;
        } else {
            throw new \Exception("Unable to retrieve access token");
        }
    }

    public function getFieldsLogin(): array
    {
        return [
            ['name' => 'tenant_id', 'type' => PasswordType::class, 'label' => 'Tenant ID'],
            ['name' => 'client_id', 'type' => PasswordType::class, 'label' => 'Client ID'],
            ['name' => 'client_secret', 'type' => PasswordType::class, 'label' => 'Client Secret'],
            ['name' => 'company_id', 'type' => PasswordType::class, 'label' => 'Company ID'],
            ['name' => 'environment', 'type' => TextType::class, 'label' => 'Environment'],
        ];
    }

    public function getObjects()
    {
        $client = new Client();
        $headers = [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];

        $companyId = $this->paramConnexion['company_id'];
        $tenantId = $this->paramConnexion['tenant_id'];
        $env = isset($this->paramConnexion['environment']) ? $this->paramConnexion['environment'] : 'production';

        $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/companies({$companyId})/customers";

        $response = $client->get($url, ['headers' => $headers]);
        $data = json_decode($response->getBody(), true);

        return $data['value'];
    }

    public function read($param)
    {
        $client = new \GuzzleHttp\Client();
        $headers = [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];

        $companyId = $this->paramConnexion['company_id'];
        $tenantId = $this->paramConnexion['tenant_id'];
        $env = isset($this->paramConnexion['environment']) ? $this->paramConnexion['environment'] : 'production';

        $module = isset($param['module']) ? $param['module'] : null;

        if ($module === 'customers') {
            $url = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$env}/api/v2.0/companies({$companyId})/customers";

            $response = $client->get($url, ['headers' => $headers]);
            $data = json_decode($response->getBody(), true);

            $results = [];
            foreach ($data['value'] as $item) {
                $results[] = [
                    'id' => $item['id'],
                    'displayName' => $item['displayName']
                ];
            }

            return $results;
        }

        throw new \Exception("Unknown object type: " . $objectName);
    }
}
?>
