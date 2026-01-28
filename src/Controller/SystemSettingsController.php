<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\ManagementSMTPType;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/rule")
 */
class SystemSettingsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @Route("/settings", name="settings_index")
     */
    public function index(Request $request): Response
    {
        // Create SMTP form for display
        $smtpForm = $this->createSmtpForm();

        // Load existing SMTP config values into the form
        $this->loadSmtpConfigIntoForm($smtpForm);

        // Get current tab from query parameter
        $activeTab = $request->query->get('tab', 'general');

        return $this->render('SystemSettings/index.html.twig', [
            'smtpForm' => $smtpForm->createView(),
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * @Route("/api/settings/elasticsearch", name="api_settings_elasticsearch", methods={"GET"})
     */
    public function getElasticsearchStatus(): JsonResponse
    {
        $configRepository = $this->entityManager->getRepository(Config::class);
        $result = $configRepository->getElasticsearchEnabled();

        $enabled = $result ? ($result['value'] === '1') : false;

        // Also check if ELASTICSEARCH_URL is configured
        $elasticsearchUrl = $_ENV['ELASTICSEARCH_URL'] ?? null;
        $isConfigured = !empty($elasticsearchUrl);

        return new JsonResponse([
            'success' => true,
            'enabled' => $enabled,
            'configured' => $isConfigured,
        ]);
    }

    /**
     * @Route("/api/settings/elasticsearch/update", name="api_settings_elasticsearch_update", methods={"POST"})
     */
    public function updateElasticsearchStatus(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['enabled'])) {
            return new JsonResponse(['error' => 'Missing enabled parameter'], 400);
        }

        $enabled = (bool) $data['enabled'];

        $configRepository = $this->entityManager->getRepository(Config::class);
        $configRepository->setElasticsearchEnabled($enabled);

        return new JsonResponse([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled
                ? $this->translator->trans('settings.messages.elasticsearch_enabled')
                : $this->translator->trans('settings.messages.elasticsearch_disabled'),
        ]);
    }

    /**
     * Create the SMTP configuration form
     */
    private function createSmtpForm(): \Symfony\Component\Form\FormInterface
    {
        $form = $this->createForm(ManagementSMTPType::class, null, [
            'action' => $this->generateUrl('management_smtp_create'),
        ]);

        $form->add('submit', SubmitType::class, [
            'label' => 'management_smtp.submit',
            'attr' => [
                'class' => 'btn btn-outline-primary mb-2',
            ],
        ]);

        $form->add('submit_test', SubmitType::class, [
            'label' => 'management_smtp.sendtestmail',
            'attr' => [
                'class' => 'btn btn-outline-primary mb-2',
            ],
        ]);

        return $form;
    }

    /**
     * Load existing SMTP configuration from .env.local into the form
     */
    private function loadSmtpConfigIntoForm(\Symfony\Component\Form\FormInterface $form): void
    {
        $envLocalPath = __DIR__ . '/../../.env.local';

        if (!file_exists($envLocalPath)) {
            return;
        }

        (new Dotenv())->load($envLocalPath);

        // Check for Brevo API key first
        $apiKey = $_ENV['BREVO_APIKEY'] ?? null;
        if (!empty($apiKey)) {
            $form->get('transport')->setData('sendinblue');
            // Don't set the API key itself for security reasons
        } else {
            // Check for MAILER_DSN
            $mailerDsn = $_ENV['MAILER_DSN'] ?? null;
            if (!empty($mailerDsn) && $mailerDsn !== 'null://localhost') {
                $this->parseMailerDsnIntoForm($form, $mailerDsn);
            }
        }

        // Load MAILER_FROM
        $mailerFrom = $_ENV['MAILER_FROM'] ?? null;
        if (!empty($mailerFrom)) {
            $form->get('sender')->setData($mailerFrom);
        }
    }

    /**
     * Parse MAILER_DSN and populate form fields
     */
    private function parseMailerDsnIntoForm(\Symfony\Component\Form\FormInterface $form, string $mailerDsn): void
    {
        // Parse DSN format: smtp://user:password@host:port?encryption=tls&auth_mode=plain
        $parsedUrl = parse_url($mailerDsn);

        if (!$parsedUrl) {
            return;
        }

        $scheme = $parsedUrl['scheme'] ?? 'smtp';
        $form->get('transport')->setData($scheme);

        if (isset($parsedUrl['host'])) {
            $form->get('host')->setData($parsedUrl['host']);
        }

        if (isset($parsedUrl['port'])) {
            $form->get('port')->setData($parsedUrl['port']);
        }

        if (isset($parsedUrl['user'])) {
            $form->get('user')->setData(urldecode($parsedUrl['user']));
        }

        // Parse query parameters for encryption and auth_mode
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);

            if (isset($queryParams['encryption'])) {
                $form->get('encryption')->setData($queryParams['encryption']);
            }

            if (isset($queryParams['auth_mode'])) {
                $form->get('auth_mode')->setData($queryParams['auth_mode']);
            }
        }
    }
}
