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

namespace App\Controller;

use App\Entity\Connector;
use App\Manager\SolutionManager;
use App\Repository\WebhookEventRepository;
use App\Service\DebugLogger;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Receive the events sent by the applications (Zoom, Eventbrite...).
 * The events are only saved in the table webhookevent, they are read later by the rules (rule param webhook)
 * so the application gets a quick response and no event is lost if a rule is locked.
 */
#[Route('/webhook', name: 'webhook_')]
class WebhookController extends AbstractController
{
    private DebugLogger $debugLogger;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private SolutionManager $solutionManager;
    private WebhookEventRepository $webhookEventRepository;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        SolutionManager $solutionManager,
        WebhookEventRepository $webhookEventRepository,
        ParameterBagInterface $parameterBag,
        DebugLogger $debugLogger
    ) {
        $this->debugLogger = $debugLogger;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->solutionManager = $solutionManager;
        $this->webhookEventRepository = $webhookEventRepository;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Token to add in the webhook url of a connector. It is calculated from the Myddleware secret so it doesn't need to be stored.
     */
    public static function getWebhookToken(int $connectorId, string $secret): string
    {
        return hash_hmac('sha256', 'webhook_'.$connectorId, $secret);
    }

    #[Route('/{connectorId}/{token}', name: 'receive', methods: ['POST'], requirements: ['connectorId' => '\d+'])]
    public function receiveAction(Request $request, int $connectorId, string $token): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['connectorId' => $connectorId]);
        $__debugReturn = null;
        try {
            try {
                // Check the token before anything else
                if (!hash_equals(self::getWebhookToken($connectorId, $this->parameterBag->get('secret')), $token)) {
                    return $__debugReturn = $this->json(['error' => 'Invalid token.'], Response::HTTP_UNAUTHORIZED);
                }

                $connector = $this->entityManager->getRepository(Connector::class)->findOneBy(['id' => $connectorId, 'deleted' => false]);
                if (empty($connector)) {
                    return $__debugReturn = $this->json(['error' => 'Connector not found.'], Response::HTTP_NOT_FOUND);
                }

                $solution = $this->solutionManager->get($connector->getSolution()->getName());
                if (!$solution->isWebhookAvailable()) {
                    return $__debugReturn = $this->json(['error' => 'Webhooks are not available for this solution.'], Response::HTTP_NOT_FOUND);
                }

                $paramConnexion = $this->getConnectorParams($connector);

                // Url validation requested by the application
                $challengeResponse = $solution->webhookChallenge($request, $paramConnexion);
                if (null !== $challengeResponse) {
                    return $__debugReturn = $challengeResponse;
                }

                if (!$solution->webhookValidate($request, $paramConnexion)) {
                    $this->logger->warning('Webhook rejected for the connector '.$connectorId.' : invalid signature.');

                    return $__debugReturn = $this->json(['error' => 'Invalid signature.'], Response::HTTP_UNAUTHORIZED);
                }

                // Save the events
                $received = 0;
                $duplicates = 0;
                foreach ($solution->webhookParse($request) as $event) {
                    if (empty($event['module']) || !isset($event['record_id']) || '' === (string) $event['record_id']) {
                        throw new Exception('Module or record_id missing in the event '.print_r($event, true));
                    }
                    if ($this->webhookEventRepository->insertEvent($connectorId, $event, new DateTime())) {
                        ++$received;
                    } else {
                        ++$duplicates;
                    }
                }

                return $__debugReturn = $this->json(['received' => $received, 'duplicates' => $duplicates]);
            } catch (Exception $e) {
                // Error 500 so the application will send the event again
                $this->logger->error('Webhook error for the connector '.$connectorId.' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

                return $__debugReturn = $this->json(['error' => 'Failed to save the webhook.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    // Get the connector params decrypted (same format as the parameter of the function login of the solutions)
    protected function getConnectorParams(Connector $connector): array
    {
        $params = [];
        $encrypter = new \Illuminate\Encryption\Encrypter(substr($this->parameterBag->get('secret'), -16));
        foreach ($connector->getConnectorParams() as $connectorParam) {
            $value = $connectorParam->getValue();
            if (is_string($value)) {
                try {
                    $value = $encrypter->decrypt($value);
                } catch (\Exception $e) { // No error if decrypt failed because some data aren't crypted
                }
            }
            $params[$connectorParam->getName()] = $value;
        }

        return $params;
    }
}
