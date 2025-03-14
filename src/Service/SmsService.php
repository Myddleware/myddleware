<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmsService
{
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private HttpClientInterface $httpClient;

    public function __construct(
        LoggerInterface $logger,
        ParameterBagInterface $params,
        HttpClientInterface $httpClient
    ) {
        $this->logger = $logger;
        $this->params = $params;
        $this->httpClient = $httpClient;
    }

    public function send(string $phoneNumber, string $message): bool
    {
        $smsProvider = $this->params->get('sms_provider', 'none');
        
        try {
            switch ($smsProvider) {
                case 'twilio':
                    return $this->sendViaTwilio($phoneNumber, $message);
                case 'mock':
                    return $this->mockSend($phoneNumber, $message);
                case 'none':
                default:
                    $this->logger->warning('SMS sending is disabled or no provider configured');
                    return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send SMS: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaTwilio(string $phoneNumber, string $message): bool
    {
        $accountSid = $this->params->get('twilio_account_sid');
        $authToken = $this->params->get('twilio_auth_token');
        $twilioNumber = $this->params->get('twilio_phone_number');
        
        if (!$accountSid || !$authToken || !$twilioNumber) {
            $this->logger->error('Twilio credentials not configured');
            return false;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        
        try {
            $response = $this->httpClient->request('POST', $url, [
                'auth_basic' => [$accountSid, $authToken],
                'body' => [
                    'From' => $twilioNumber,
                    'To' => $phoneNumber,
                    'Body' => $message,
                ],
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode >= 200 && $statusCode < 300) {
                return true;
            }
            
            $this->logger->error('Twilio API error: ' . $response->getContent(false));
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Twilio API exception: ' . $e->getMessage());
            return false;
        }
    }

    private function mockSend(string $phoneNumber, string $message): bool
    {
        $this->logger->info('MOCK SMS to ' . $phoneNumber . ': ' . $message);
        return true;
    }
} 