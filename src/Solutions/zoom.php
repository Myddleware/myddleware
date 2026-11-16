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
 *  - meeting_participants / webinar_participants : read by webhook only (meeting.participant_joined / meeting.participant_left...).
 *    The record is built from the event payload (no API call, so it works with a free Zoom account).
 *    Each event is a record : a participant who joins, leaves and joins again generates 3 records.
 *    The id of a record is <meeting id>|<meeting uuid>|<participant key>|<joined or left>|<event time>.
 *  - meeting_events / webinar_events : read by webhook only (meeting.started / meeting.ended...), one record by event.
 *    The id of a record is <meeting id>|<meeting uuid>|<started or ended>.
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
        'meeting_invitees' => ['id', 'created_at'],
        'webinars' => ['id', 'created_at'],
        'meeting_participants' => ['id', 'event_time'],
        'webinar_participants' => ['id', 'event_time'],
        'meeting_events' => ['id', 'event_time'],
        'webinar_events' => ['id', 'event_time'],
    ];

    // Modules built from the webhook payloads only (no API call, available with a free Zoom account)
    protected array $webhookOnlyModules = ['meeting_participants', 'webinar_participants', 'meeting_events', 'webinar_events'];

    // Webhook events managed by Myddleware : event => [module, deletion, participant event]
    // We don't use the API past_meetings/participants because it requires a paid account
    protected array $webhookEvents = [
        // The invitees of the meeting are read too (settings.meeting_invitees of the meeting)
        'meeting.created' => ['module' => 'meetings', 'otherModules' => ['meeting_invitees']],
        'meeting.updated' => ['module' => 'meetings', 'otherModules' => ['meeting_invitees']],
        'meeting.deleted' => ['module' => 'meetings', 'deletion' => true],
        'meeting.started' => ['module' => 'meeting_events'],
        'meeting.ended' => ['module' => 'meeting_events'],
        'meeting.participant_joined' => ['module' => 'meeting_participants', 'participant' => true],
        'meeting.participant_left' => ['module' => 'meeting_participants', 'participant' => true],
        'webinar.created' => ['module' => 'webinars'],
        'webinar.updated' => ['module' => 'webinars'],
        'webinar.deleted' => ['module' => 'webinars', 'deletion' => true],
        'webinar.started' => ['module' => 'webinar_events'],
        'webinar.ended' => ['module' => 'webinar_events'],
        'webinar.participant_joined' => ['module' => 'webinar_participants', 'participant' => true],
        'webinar.participant_left' => ['module' => 'webinar_participants', 'participant' => true],
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
            'meeting_invitees' => 'Meeting invitees',
            'meeting_events' => 'Meeting events (started / ended)',
            'webinar_events' => 'Webinar events (started / ended)',
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
                        'topic' => 'Topic',
                        'participant_uuid' => 'Participant UUID',
                        'user_id' => ['label' => 'User ID', 'relate' => 1],
                        'registrant_id' => ['label' => 'Registrant ID', 'relate' => 1],
                        'name' => 'Name',
                        'user_email' => 'Email',
                        'event' => 'Event (joined / left)',
                        'event_time' => 'Event time',
                        'join_time' => 'Join time',
                        'leave_time' => 'Leave time',
                        'leave_reason' => 'Leave reason',
                    ];
                    break;
                case 'meeting_invitees':
                    $fields = [
                        'meeting_id' => ['label' => 'Meeting ID', 'relate' => 1],
                        'topic' => 'Topic',
                        'start_time' => 'Start time',
                        'email' => 'Email',
                        'internal_user' => 'Internal user (1 / 0)',
                        'created_at' => 'Meeting created at',
                    ];
                    break;
                case 'meeting_events':
                case 'webinar_events':
                    $parent = ('meeting_events' == $module ? 'meeting' : 'webinar');
                    $fields = [
                        $parent.'_id' => ['label' => ucfirst($parent).' ID', 'relate' => 1],
                        $parent.'_uuid' => ucfirst($parent).' UUID',
                        'topic' => 'Topic',
                        'type' => 'Type',
                        'host_id' => ['label' => 'Host ID', 'relate' => 1],
                        'event' => 'Event (started / ended)',
                        'event_time' => 'Event time',
                        'start_time' => 'Start time',
                        'end_time' => 'End time',
                        'duration' => 'Scheduled duration (minutes)',
                        'timezone' => 'Timezone',
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
        // The events (joined, left, started, ended) only exist in the webhooks, they can't be read with the API
        if (in_array($module, $this->webhookOnlyModules)) {
            $this->logger->info('The module '.$module.' can only be read by webhook.');

            return $result;
        }

        // Read a specific record (call by webhook, rerun, readrecord command...)
        if (!empty($param['query']['id'])) {
            // Invitees : the id is <meeting id> (all the invitees of the meeting) or <meeting id>|<email>
            if ('meeting_invitees' == $module) {
                $idParts = explode('|', $param['query']['id'], 2);

                return $this->readInvitees($idParts[0], $idParts[1] ?? null);
            }
            $record = $this->zoomCall($module.'/'.$this->encodeUuid($param['query']['id']));
            if (!empty($record)) {
                $result[] = $record;
            }

            return $result;
        }

        // Read the meetings / webinars created after the reference date (Zoom doesn't return the modification date)
        // For the invitees, we read the meetings created after the reference date then the invitees of each meeting
        $listModule = ('meeting_invitees' == $module ? 'meetings' : $module);
        $nextPageToken = '';
        do {
            $response = $this->zoomCall('users/me/'.$listModule, [
                // Type available only for the meetings
                'type' => ('meetings' == $listModule ? 'scheduled' : ''),
                'page_size' => $this->pageSize,
                'next_page_token' => $nextPageToken,
            ]);
            foreach ($response[$listModule] ?? [] as $record) {
                if ($this->dateTimeToMyddleware($record['created_at']) > $param['date_ref']) {
                    if ('meeting_invitees' == $module) {
                        $result = array_merge($result, $this->readInvitees((string) $record['id']));
                    } else {
                        $result[] = $record;
                    }
                }
            }
            $nextPageToken = $response['next_page_token'] ?? '';
        } while (!empty($nextPageToken));

        return $result;
    }

    // Invitees of a meeting (settings.meeting_invitees), one record by invitee : <meeting id>|<email>
    protected function readInvitees(string $meetingId, ?string $email = null): array
    {
        $result = [];
        $meeting = $this->zoomCall('meetings/'.$this->encodeUuid($meetingId));
        foreach ($meeting['settings']['meeting_invitees'] ?? [] as $invitee) {
            if (empty($invitee['email']) || (null !== $email && $invitee['email'] !== $email)) {
                continue;
            }
            $result[] = [
                'id' => $meetingId.'|'.$invitee['email'],
                'meeting_id' => $meetingId,
                'topic' => $meeting['topic'] ?? '',
                'start_time' => $meeting['start_time'] ?? '',
                'email' => $invitee['email'],
                'internal_user' => (empty($invitee['internal_user']) ? 0 : 1),
                'created_at' => $meeting['created_at'] ?? '',
            ];
        }

        return $result;
    }

    public function getRefFieldName($param)
    {
        if (in_array($param['module'], $this->webhookOnlyModules)) {
            return 'event_time';
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
        $recordId = $object['id'];
        // For the participants : <meeting id>|<meeting uuid>|<participant key>
        if (!empty($eventConfig['participant'])) {
            if (empty($object['participant'])) {
                return [];
            }
            // One record by event : <meeting id>|<meeting uuid>|<participant key>|<joined or left>|<event time>
            $action = (str_ends_with($event, '_left') ? 'left' : 'joined');
            $eventTime = $object['participant']['left' == $action ? 'leave_time' : 'join_time'] ?? ($body['event_ts'] ?? '');
            $recordId = $object['id'].'|'.($object['uuid'] ?? '').'|'.$this->getParticipantKey($object['participant']).'|'.$action.'|'.$eventTime;
        // Meeting events : <meeting id>|<meeting uuid>|<started or ended> (a meeting instance starts and ends once)
        } elseif (in_array($eventConfig['module'], ['meeting_events', 'webinar_events'])) {
            $recordId = $object['id'].'|'.($object['uuid'] ?? '').'|'.$this->getMeetingAction($event);
        }

        $events = [];
        foreach (array_merge([$eventConfig['module']], $eventConfig['otherModules'] ?? []) as $module) {
            $events[] = [
                'module' => $module,
                // Other modules (e.g. meeting invitees) are read with the meeting id
                'record_id' => ($module == $eventConfig['module'] ? $recordId : $object['id']),
                'event_type' => $event,
                // Zoom sends the same body when it retries a webhook. Module added because one event can feed several modules
                'external_id' => sha1($request->getContent()).'|'.$module,
                'deletion' => !empty($eventConfig['deletion']),
                'payload' => $body,
            ];
        }

        return $events;
    }

    public function isWebhookAvailable(): bool
    {
        return true;
    }

    // The participants and the meeting events are built from the event payload, the other modules are read with the API
    public function getWebhookRecord(string $module, array $payloads): ?array
    {
        if (!in_array($module, $this->webhookOnlyModules)) {
            return null;
        }
        // One record by event, the record id identifies the event so there is only one payload (Zoom retries are ignored)
        $payload = end($payloads);
        $object = $payload['payload']['object'] ?? [];

        if (in_array($module, ['meeting_events', 'webinar_events'])) {
            $parent = ('meeting_events' == $module ? 'meeting' : 'webinar');
            $action = $this->getMeetingAction($payload['event'] ?? '');

            return [
                $parent.'_id' => (string) ($object['id'] ?? ''),
                $parent.'_uuid' => $object['uuid'] ?? '',
                'topic' => $object['topic'] ?? '',
                'type' => $object['type'] ?? '',
                'host_id' => $object['host_id'] ?? '',
                'event' => $action,
                'event_time' => $object['ended' == $action ? 'end_time' : 'start_time'] ?? '',
                'start_time' => $object['start_time'] ?? '',
                'end_time' => $object['end_time'] ?? '',
                'duration' => $object['duration'] ?? '',
                'timezone' => $object['timezone'] ?? '',
            ];
        }

        $participant = $object['participant'] ?? [];
        // Event without participant (e.g. meeting.ended received by the previous version of the connector)
        if (empty($participant)) {
            return [];
        }
        $parent = ('meeting_participants' == $module ? 'meeting' : 'webinar');
        $action = (str_ends_with($payload['event'] ?? '', '_left') ? 'left' : 'joined');

        return [
            $parent.'_id' => (string) ($object['id'] ?? ''),
            $parent.'_uuid' => $object['uuid'] ?? '',
            'topic' => $object['topic'] ?? '',
            'participant_uuid' => $participant['participant_uuid'] ?? '',
            'user_id' => $participant['id'] ?? '',
            'registrant_id' => $participant['registrant_id'] ?? '',
            'name' => $participant['user_name'] ?? '',
            'user_email' => $participant['email'] ?? '',
            'event' => $action,
            'event_time' => $participant['left' == $action ? 'leave_time' : 'join_time'] ?? '',
            'join_time' => $participant['join_time'] ?? '',
            'leave_time' => $participant['leave_time'] ?? '',
            'leave_reason' => $participant['leave_reason'] ?? '',
        ];
    }

    // meeting.started => started, meeting.ended => ended
    protected function getMeetingAction(string $event): string
    {
        return (str_ends_with($event, '.ended') ? 'ended' : 'started');
    }

    // Key of a participant in a meeting instance : participant uuid, or Zoom user id, or email, or name
    protected function getParticipantKey(array $participant): string
    {
        foreach (['participant_uuid', 'id', 'email'] as $field) {
            if (!empty($participant[$field])) {
                return $participant[$field];
            }
        }

        return substr(sha1($participant['user_name'] ?? ''), 0, 16);
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
