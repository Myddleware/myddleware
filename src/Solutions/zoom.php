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

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zoom connector (source only).
 * Authentication : Server-to-Server OAuth app (Zoom App Marketplace), https://developers.zoom.us/docs/internal-apps/s2s-oauth/
 * Webhooks : event subscription of the same app, url https://<myddleware>/webhook/<connectorId>/<token>
 *
 * Modules :
 *  - meetings / webinars : read by creation date or by webhook (meeting.created, meeting.updated, meeting.deleted...)
 *  - meeting_participants / webinar_participants : read by webhook only (meeting.ended / webinar.ended).
 *    When a meeting ends, Myddleware reads all its participants.
 *    The id of a participant is <meeting id>|<meeting uuid>|<participant key> (one record by join session).
 */
class zoom extends solution
{
    protected string $oauthUrl = 'https://zoom.us/oauth/token';
    protected string $apiUrl = 'https://api.zoom.us/v2';
    protected string $accessToken = '';
    // Max page size allowed by Zoom
    protected int $pageSize = 300;
    // A webhook request older than this delay (in seconds) is rejected (replay attack)
    protected int $webhookMaxDelay = 300;

    protected bool $readDeletion = true;

    protected array $required_fields = [
        'meetings' => ['id', 'created_at'],
        'webinars' => ['id', 'created_at'],
        'meeting_participants' => ['id', 'join_time'],
        'webinar_participants' => ['id', 'join_time'],
    ];

    // Webhook events managed by Myddleware : event => [module, read the participants (event end)]
    protected array $webhookEvents = [
        'meeting.created' => ['module' => 'meetings'],
        'meeting.updated' => ['module' => 'meetings'],
        'meeting.deleted' => ['module' => 'meetings', 'deletion' => true],
        'meeting.ended' => ['module' => 'meeting_participants', 'participants' => true],
        'webinar.created' => ['module' => 'webinars'],
        'webinar.updated' => ['module' => 'webinars'],
        'webinar.deleted' => ['module' => 'webinars', 'deletion' => true],
        'webinar.ended' => ['module' => 'webinar_participants', 'participants' => true],
    ];

    public function getFieldsLogin(): array
    {
        return [
            [
                'name' => 'accountid',
                'type' => TextType::class,
                'label' => 'solution.fields.accountid',
            ],
            [
                'name' => 'clientid',
                'type' => TextType::class,
                'label' => 'solution.fields.clientid',
            ],
            [
                'name' => 'clientsecret',
                'type' => PasswordType::class,
                'label' => 'solution.fields.clientsecret',
            ],
            [
                'name' => 'webhooksecret',
                'type' => PasswordType::class,
                'label' => 'solution.fields.webhooksecret',
            ],
        ];
    }

    // Get an access token with the Server-to-Server OAuth credentials then check it with the current user
    public function login($paramConnexion)
    {
        parent::login($paramConnexion);
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->oauthUrl.'?'.http_build_query([
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->paramConnexion['accountid'],
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic '.base64_encode($this->paramConnexion['clientid'].':'.$this->paramConnexion['clientsecret']),
                ],
            ]);
            $response = json_decode(curl_exec($curl), true);
            $curlError = curl_error($curl);
            curl_close($curl);
            if (!empty($curlError)) {
                throw new \Exception('Failed to call Zoom : '.$curlError);
            }
            if (empty($response['access_token'])) {
                throw new \Exception('Failed to get the Zoom access token : '.($response['reason'] ?? $response['error'] ?? print_r($response, true)));
            }
            $this->accessToken = $response['access_token'];

            // Check the token
            $this->zoomCall('users/me');
            $this->connexion_valide = true;
        } catch (\Exception $e) {
            $error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $this->logger->error($error);

            return ['error' => $error];
        }
    }

    public function get_modules($type = 'source')
    {
        if ('source' != $type) {
            return [];
        }

        return [
            'meetings' => 'Meetings',
            'webinars' => 'Webinars',
            'meeting_participants' => 'Meeting participants',
            'webinar_participants' => 'Webinar participants',
        ];
    }

    public function get_module_fields($module, $type = 'source', $param = null): array
    {
        parent::get_module_fields($module, $type);
        try {
            $fields = [];
            switch ($module) {
                case 'meetings':
                case 'webinars':
                    $fields = [
                        'uuid' => 'UUID',
                        'topic' => 'Topic',
                        'type' => 'Type',
                        'status' => 'Status',
                        'agenda' => 'Agenda',
                        'start_time' => 'Start time',
                        'duration' => 'Duration (minutes)',
                        'timezone' => 'Timezone',
                        'join_url' => 'Join URL',
                        'created_at' => 'Created at',
                        'host_id' => ['label' => 'Host ID', 'relate' => 1],
                        'host_email' => 'Host email',
                    ];
                    break;
                case 'meeting_participants':
                case 'webinar_participants':
                    $parent = ('meeting_participants' == $module ? 'meeting' : 'webinar');
                    $fields = [
                        $parent.'_id' => ['label' => ucfirst($parent).' ID', 'relate' => 1],
                        $parent.'_uuid' => ucfirst($parent).' UUID',
                        'user_id' => ['label' => 'User ID', 'relate' => 1],
                        'registrant_id' => ['label' => 'Registrant ID', 'relate' => 1],
                        'name' => 'Name',
                        'user_email' => 'Email',
                        'join_time' => 'Join time',
                        'leave_time' => 'Leave time',
                        'duration' => 'Duration (seconds)',
                        'status' => 'Status',
                    ];
                    break;
            }
            foreach ($fields as $name => $field) {
                $field = (is_array($field) ? $field : ['label' => $field]);
                $this->moduleFields[$name] = [
                    'label' => $field['label'],
                    'type' => 'varchar(255)',
                    'type_bdd' => 'varchar(255)',
                    'required' => 0,
                    'required_relationship' => 0,
                    'relate' => $field['relate'] ?? 0,
                ];
            }

            return $this->moduleFields;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().' '.$e->getLine());

            return [];
        }
    }

    // Rule param to read the data from the webhooks
    public function getFieldsParamUpd($type, $module): array
    {
        if ('source' == $type) {
            return [$this->getWebhookParam()];
        }

        return [];
    }

    public function read($param)
    {
        $result = [];
        $module = $param['module'];
        $isParticipantModule = in_array($module, ['meeting_participants', 'webinar_participants']);

        // Read a specific record (call by webhook, rerun, readrecord command...)
        if (!empty($param['query']['id'])) {
            if ($isParticipantModule) {
                return $this->readParticipants($module, $param['query']['id']);
            }
            $record = $this->zoomCall($module.'/'.$this->encodeUuid($param['query']['id']));
            if (!empty($record)) {
                $result[] = $record;
            }

            return $result;
        }

        // The participants can't be read by date, Zoom doesn't have any call to get the participants modified after a date
        if ($isParticipantModule) {
            $this->logger->info('The module '.$module.' can only be read by webhook (event '.('meeting_participants' == $module ? 'meeting.ended' : 'webinar.ended').').');

            return $result;
        }

        // Read the meetings / webinars created after the reference date (Zoom doesn't return the modification date)
        $nextPageToken = '';
        do {
            $response = $this->zoomCall('users/me/'.$module, [
                // Type available only for the meetings
                'type' => ('meetings' == $module ? 'scheduled' : ''),
                'page_size' => $this->pageSize,
                'next_page_token' => $nextPageToken,
            ]);
            foreach ($response[$module] ?? [] as $record) {
                if ($this->dateTimeToMyddleware($record['created_at']) > $param['date_ref']) {
                    $result[] = $record;
                }
            }
            $nextPageToken = $response['next_page_token'] ?? '';
        } while (!empty($nextPageToken));

        return $result;
    }

    // Read all participants of a meeting / webinar instance. $id is <meeting id>|<meeting uuid> or the id of a participant
    protected function readParticipants(string $module, string $id): array
    {
        $idParts = explode('|', $id);
        if (count($idParts) < 2) {
            throw new \Exception('Invalid participant id '.$id.'. Expected format : <meeting id>|<meeting uuid>[|<participant key>].');
        }
        [$meetingId, $meetingUuid] = $idParts;
        $participantKey = $idParts[2] ?? null;
        $parent = ('meeting_participants' == $module ? 'meeting' : 'webinar');

        $result = [];
        $nextPageToken = '';
        do {
            $response = $this->zoomCall('past_'.$parent.'s/'.$this->encodeUuid($meetingUuid).'/participants', [
                'page_size' => $this->pageSize,
                'next_page_token' => $nextPageToken,
            ]);
            foreach ($response['participants'] ?? [] as $participant) {
                // No unique id returned by Zoom for a join session, we calculate a stable key
                $key = substr(sha1(($participant['id'] ?? '').'|'.($participant['user_email'] ?? '').'|'.($participant['name'] ?? '').'|'.($participant['join_time'] ?? '')), 0, 16);
                if (null !== $participantKey && $key !== $participantKey) {
                    continue;
                }
                $participant['user_id'] = $participant['id'] ?? '';
                $participant['id'] = $meetingId.'|'.$meetingUuid.'|'.$key;
                $participant[$parent.'_id'] = $meetingId;
                $participant[$parent.'_uuid'] = $meetingUuid;
                $result[] = $participant;
            }
            $nextPageToken = $response['next_page_token'] ?? '';
        } while (!empty($nextPageToken));

        return $result;
    }

    public function getRefFieldName($param)
    {
        if (in_array($param['module'], ['meeting_participants', 'webinar_participants'])) {
            return 'join_time';
        }

        return 'created_at';
    }

    // Zoom url validation (event endpoint.url_validation) : https://developers.zoom.us/docs/api/webhooks/#validate-your-webhook-endpoint
    public function webhookChallenge(Request $request, array $paramConnexion): ?Response
    {
        $body = json_decode($request->getContent(), true);
        if ('endpoint.url_validation' != ($body['event'] ?? '')) {
            return null;
        }
        if (empty($paramConnexion['webhooksecret']) || empty($body['payload']['plainToken'])) {
            return new JsonResponse(['error' => 'Webhook secret token missing.'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'plainToken' => $body['payload']['plainToken'],
            'encryptedToken' => hash_hmac('sha256', $body['payload']['plainToken'], $paramConnexion['webhooksecret']),
        ]);
    }

    // Check the signature of the request : https://developers.zoom.us/docs/api/webhooks/#verify-webhook-events
    public function webhookValidate(Request $request, array $paramConnexion): bool
    {
        $timestamp = $request->headers->get('x-zm-request-timestamp');
        $signature = $request->headers->get('x-zm-signature');
        if (
               empty($paramConnexion['webhooksecret'])
            || empty($timestamp)
            || empty($signature)
            || abs(time() - (int) $timestamp) > $this->webhookMaxDelay
        ) {
            return false;
        }
        $expected = 'v0='.hash_hmac('sha256', 'v0:'.$timestamp.':'.$request->getContent(), $paramConnexion['webhooksecret']);

        return hash_equals($expected, $signature);
    }

    public function webhookParse(Request $request): array
    {
        $body = json_decode($request->getContent(), true);
        $event = $body['event'] ?? '';
        $object = $body['payload']['object'] ?? [];
        // Event not managed by Myddleware
        if (empty($this->webhookEvents[$event]) || empty($object['id'])) {
            return [];
        }
        $eventConfig = $this->webhookEvents[$event];

        return [[
            'module' => $eventConfig['module'],
            // For the participants, the record read is the instance of the meeting : <meeting id>|<meeting uuid>
            'record_id' => (empty($eventConfig['participants']) ? $object['id'] : $object['id'].'|'.($object['uuid'] ?? '')),
            'event_type' => $event,
            // Zoom sends the same body when it retries a webhook
            'external_id' => sha1($request->getContent()),
            'deletion' => !empty($eventConfig['deletion']),
            'payload' => $body,
        ]];
    }

    public function isWebhookAvailable(): bool
    {
        return true;
    }

    // 2026-10-23T10:00:00Z to 2026-10-23 10:00:00 (UTC)
    protected function dateTimeToMyddleware($dateTime)
    {
        $dto = new \DateTime($dateTime);
        $dto->setTimezone(new \DateTimeZone('UTC'));

        return $dto->format('Y-m-d H:i:s');
    }

    // A UUID beginning with / or containing // has to be encoded twice
    protected function encodeUuid(string $uuid): string
    {
        $encoded = rawurlencode($uuid);
        if (str_starts_with($uuid, '/') || str_contains($uuid, '//')) {
            $encoded = rawurlencode($encoded);
        }

        return $encoded;
    }

    // Call the Zoom API (GET) and return the response decoded
    protected function zoomCall(string $endpoint, array $query = []): array
    {
        $query = array_filter($query, function ($value) {
            return '' !== $value && null !== $value;
        });
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl.'/'.$endpoint.(empty($query) ? '' : '?'.http_build_query($query)),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$this->accessToken,
                'Content-Type: application/json',
            ],
        ]);
        $rawResponse = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        if (!empty($curlError)) {
            throw new \Exception('Failed to call Zoom ('.$endpoint.') : '.$curlError);
        }
        $response = json_decode($rawResponse, true) ?? [];
        // Record not found (e.g. meeting deleted), no error, the record is just not returned
        if (404 == $httpCode) {
            return [];
        }
        if ($httpCode >= 400) {
            throw new \Exception('Zoom error '.$httpCode.' ('.$endpoint.') : '.($response['message'] ?? $rawResponse));
        }

        return $response;
    }
}
