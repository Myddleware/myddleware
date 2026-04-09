<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\ManagementSMTPType;
use App\Repository\ConfigRepository;
use App\Service\DebugLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/settings')]
class SystemSettingsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private ConfigRepository $configRepository;
    private DebugLogger $debugLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ConfigRepository $configRepository,
        DebugLogger $debugLogger
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->configRepository = $configRepository;
        $this->debugLogger = $debugLogger;
    }

    #[Route('/', name: 'settings_index')]
    public function index(Request $request): Response
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
            $smtpForm = $this->createSmtpForm();

        // Load existing SMTP config values into the form
            $this->loadSmtpConfigIntoForm($smtpForm);

            $activeTab = $request->query->get('tab', 'general');

            return $__debugReturn = $this->render('SystemSettings/index.html.twig', [
                'smtpForm' => $smtpForm->createView(),
                'activeTab' => $activeTab,
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/api/debug-mode', name: 'api_settings_debug_mode', methods: ['GET'])]
    public function getDebugMode(): JsonResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                return $__debugReturn = new JsonResponse(['error' => 'Permission denied'], 403);
            }

            return $__debugReturn = new JsonResponse([
                'success' => true,
                'enabled' => $this->configRepository->getDebugMode(),
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/api/debug-mode/toggle', name: 'api_settings_debug_mode_toggle', methods: ['POST'])]
    public function toggleDebugMode(Request $request): JsonResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                return $__debugReturn = new JsonResponse(['error' => 'Permission denied'], 403);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['enabled'])) {
                return $__debugReturn = new JsonResponse(['error' => 'Missing enabled parameter'], 400);
            }

            $enabled = (bool) $data['enabled'];
            $this->configRepository->setDebugMode($enabled);

            return $__debugReturn = new JsonResponse([
                'success' => true,
                'enabled' => $enabled,
                'level' => $this->configRepository->getLogLevel(),
                'message' => $enabled
                    ? $this->translator->trans('settings.messages.debug_enabled')
                    : $this->translator->trans('settings.messages.debug_disabled'),
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/api/log-level', name: 'api_settings_log_level', methods: ['GET'])]
    public function getLogLevel(): JsonResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                return $__debugReturn = new JsonResponse(['error' => 'Permission denied'], 403);
            }

            return $__debugReturn = new JsonResponse([
                'success' => true,
                'level' => $this->configRepository->getLogLevel(),
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    #[Route('/api/log-level/update', name: 'api_settings_log_level_update', methods: ['POST'])]
    public function updateLogLevel(Request $request): JsonResponse
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['request' => $request]);
        $__debugReturn = null;
        try {
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                return $__debugReturn = new JsonResponse(['error' => 'Permission denied'], 403);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['level'])) {
                return $__debugReturn = new JsonResponse(['error' => 'Missing level parameter'], 400);
            }

            $validLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
            $level = strtolower($data['level']);

            if (!in_array($level, $validLevels)) {
                return $__debugReturn = new JsonResponse(['error' => 'Invalid log level'], 400);
            }

            $this->configRepository->setLogLevel($level);

            return $__debugReturn = new JsonResponse([
                'success' => true,
                'level' => $level,
                'message' => $this->translator->trans('settings.messages.log_level_updated', ['%level%' => $level]),
            ]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function createSmtpForm(): \Symfony\Component\Form\FormInterface
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
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

            return $__debugReturn = $form;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function loadSmtpConfigIntoForm(\Symfony\Component\Form\FormInterface $form): void
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['form' => $form]);
        try {
            $envLocalPath = __DIR__ . '/../../.env.local';

            if (!file_exists($envLocalPath)) {
                return;
            }

            (new Dotenv())->load($envLocalPath);

            $apiKey = $_ENV['BREVO_APIKEY'] ?? null;
            if (!empty($apiKey)) {
                $form->get('transport')->setData('sendinblue');
            } else {
                $mailerDsn = $_ENV['MAILER_DSN'] ?? null;
                if (!empty($mailerDsn) && $mailerDsn !== 'null://localhost') {
                    $this->parseMailerDsnIntoForm($form, $mailerDsn);
                }
            }

            $mailerFrom = $_ENV['MAILER_FROM'] ?? null;
            if (!empty($mailerFrom)) {
                $form->get('sender')->setData($mailerFrom);
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    private function parseMailerDsnIntoForm(\Symfony\Component\Form\FormInterface $form, string $mailerDsn): void
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['form' => $form, 'mailerDsn' => $mailerDsn]);
        try {
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

            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);

                if (isset($queryParams['encryption'])) {
                    $form->get('encryption')->setData($queryParams['encryption']);
                }

                if (isset($queryParams['auth_mode'])) {
                    $form->get('auth_mode')->setData($queryParams['auth_mode']);
                }
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }
}
