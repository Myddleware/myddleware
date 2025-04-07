<?php

/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\User;

use Twig\Environment;
use App\Entity\Config;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Psr\Log\LoggerInterface;
use Twig\Error\RuntimeError;
use Doctrine\DBAL\Connection;
use App\Repository\JobRepository;
use App\Repository\LogRepository;
use App\Repository\RuleRepository;
use App\Repository\UserRepository;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class NotificationManager
{
    protected EntityManagerInterface $entityManager;
    protected $emailAddresses = array();
    protected $configParams;
    protected ToolsManager $tools;
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private Connection $connection;
    private MailerInterface $mailer;
    private UserRepository $userRepository;
    private TranslatorInterface $translator;
    private JobRepository $jobRepository;
    private RuleRepository $ruleRepository;
    private $fromEmail;
    private Environment $twig;
    private ConfigRepository $configRepository;
    private LogRepository $logRepository;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        UserRepository $userRepository,
        JobRepository $jobRepository,
        RuleRepository $ruleRepository,
        MailerInterface $mailer,
        ToolsManager $tools,
        ParameterBagInterface $params,
        Environment $twig,
        ConfigRepository $configRepository, 
        LogRepository $logRepository
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->jobRepository = $jobRepository;
        $this->ruleRepository = $ruleRepository;
        $this->mailer = $mailer;
        $this->tools = $tools;
        $this->params = $params;
        $this->twig = $twig;
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * Send alert
     *
     * @throws Exception
     */
    public function sendAlert(): bool
    {
        try {

            $this->sendAlertTaskTooLong();
            // $this->sendAlertLimitReached();

            return true;

        } catch (Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            throw new Exception($error);
        }
    }

     /**
     * Send alert if a job is running too long.
     *
     * @throws Exception
     */
    public function sendAlertTaskTooLong()
    {
        // Set all config parameters
        $this->setConfigParam();
        if (empty($this->configParams['alert_time_limit'])) {
            throw new Exception('No alert time set in the parameters file. Please set the parameter alert_limit_minute in the file config/parameters.yml.');
        }
        // Calculate the date corresponding to the beginning still authorised
        $timeLimit = new DateTime('now', new \DateTimeZone('GMT'));
        $timeLimit->modify('-'.$this->configParams['alert_time_limit'].' minutes');

        // Search if a job is lasting more time that the limit authorized
        $job = $this->jobRepository->findJobStarted($timeLimit);
        // If a job is found, we send the alert
        if (!empty($job)) {
            // Create text
            $textMail = $this->translator->trans('email_alert.body', [
                '%min%' => $this->configParams['alert_time_limit'],
                '%begin%' => $job->getBegin()->format('Y-m-d H:i:s'),
                '%id%' => $job->getId(),
                '%base_uri%' => (!empty($this->configParams['base_uri']) ? $this->configParams['base_uri'].'rule/task/view/'.$job->getId().'/log' : ''),
            ]);

            return $this->send($textMail, $this->translator->trans('email_alert.subject'));
        }
    }

    /**
     * Send alert if a batch of documents has the same reference while having more documents than the limit, which will cause a bottleneck. It takas an array of JobSettings as parameter. It returns true if the alert is sent using the function send() and false if the alert is not sent.
     *
     * @throws Exception
     */
    public function sendAlertSameDocReference(array $JobSettings): bool
    {
        $this->setConfigParam();
        // we create a text mail with the rule id, the job id and the reference date, they are contained in the JobSettings array
        $textMail = $this->translator->trans('email_alert_same_doc_reference.body', [
            '%rule_id%' => $JobSettings['rule_id'],
            '%job_id%' => $JobSettings['job_id'],
            '%reference_date%' => $JobSettings['reference_date'],
            '%base_uri%' => (!empty($this->configParams['base_uri']) ? $this->configParams['base_uri'].'rule/task/view/'.$JobSettings['job_id'].'/log' : ''),
        ]);

        return $this->send($textMail, $this->translator->trans('email_alert_same_doc_reference.subject'));

    }


    /**
     * Send alert if limit reached.
     *
     * @throws Exception
     */
    public function sendAlertLimitReached()
    {
        // Get alert_date_ref
        $alertDateRef = $this->configRepository->findAlertDateRef();

        $alertDateRef = $alertDateRef['value'];

        // Get error message
        $newErrorLogs = $this->logRepository->findNewErrorLogs(new \DateTime($alertDateRef));

        //Send Alerte
        if (!empty($newErrorLogs)) {

			$textMail = "Des nouveaux logs d'erreur ont été trouvés :\n\n";

			// TODO: à translate
			foreach ($newErrorLogs as $log) {
				$textMail .= "Date de création: " . $log['created']->format('Y-m-d H:i:s') . "\n";
				$textMail .= "Type: " . $log['type'] . "\n";
				$textMail .= "Message: " . $log['message'] . "\n\n";
			}

			// TODO: check : envoyez l'e-mail
			$this->send($textMail, "Alerte: Nouveaux logs d'erreur trouvés");
        }
        // Update alert_date_ref
        $currentDate = new \DateTime();
        $this->configRepository->setAlertDateRef($currentDate->format('Y-m-d H:i:s'));

	}
		
	protected function send($textMail, $subject) {
		// Get the email adresses of all ADMIN
		$this->setEmailAddresses();
		// Check that we have at least one email address
		if (empty($this->emailAddresses)) {
			throw new Exception('No email address found to send notification. You should have at least one admin user with an email address.');
		}
		
		if (!empty($_ENV['BREVO_APIKEY'])) {
            $config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $_ENV['BREVO_APIKEY']);
            $apiInstance = new \Brevo\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $config);
            $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail();
            
            // Create sender object
            $sender = new \Brevo\Client\Model\SendSmtpEmailSender();
            $sender->setEmail($this->configParams['email_from'] ?? 'no-reply@myddleware.com');
            
            // Create recipients array
            $recipients = [];
            foreach ($this->emailAddresses as $emailAddress) {
                $recipient = new \Brevo\Client\Model\SendSmtpEmailTo();
                $recipient->setEmail($emailAddress);
                $recipients[] = $recipient;
            }
            
            // Set up the email
            $sendSmtpEmail->setSender($sender);
            $sendSmtpEmail->setTo($recipients);
            $sendSmtpEmail->setSubject($subject);
            $sendSmtpEmail->setHtmlContent($textMail);

            try {
                $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            } catch (Exception $e) {
                throw new Exception('Exception when calling TransactionalEmailsApi->sendTransacEmail: '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            }
        } else {
            // Send the message to all admins
            foreach ($this->emailAddresses as $emailAddress) {
                $email = (new Email())
                    ->from($this->configParams['email_from'] ?? 'no-reply@myddleware.com')
                    ->to($emailAddress)
                    ->subject($subject)
                    ->html($textMail);
                
                try {
                    $this->mailer->send($email);
                } catch (Exception $e) {
                    $this->logger->error('Failed to send alert email : '.$textMail.' to '.$emailAddress);
                    throw new Exception('Failed to send alert email : '.$textMail.' to '.$emailAddress);
                }
            }
        }
        return true;
	}


    // Send notification to receive statistique about myddleware data transfer
    public function sendNotification()
    {
        try {
            // Set all config parameters
            $this->setConfigParam();
            // Get the email adresses of all ADMIN
            $this->setEmailAddresses();
            // Check that we have at least one email address
            if (empty($this->emailAddresses)) {
                throw new Exception('No email address found to send notification. You should have at least one admin user with an email address.');
            }
            // Récupération du nombre de données transférées depuis la dernière notification. On en compte qu'une fois les erreurs
			$limitDate = new DateTime('now', new \DateTimeZone('GMT'));
			$limitDate->modify('-24 hours');
				
            $sqlParams = "	SELECT
								count(document.id) cpt,
								document.global_status
							FROM document
							WHERE
									document.deleted = 0
								AND document.date_modified > :limitDate	   
							GROUP BY document.global_status";
            $stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue('limitDate', $limitDate->format('Y-m-d H:i:s'));
            $result = $stmt->executeQuery();
            $cptLogs = $result->fetchAllAssociative();
            $job_open = 0;
            $job_close = 0;
            $job_error = 0;
            $job_cancel = 0;
            if (!empty($cptLogs)) {
                foreach ($cptLogs as $cptLog) {
                    switch ($cptLog['global_status']) {
                        case 'Open':
                            $job_open = $cptLog['cpt'];
                            break;
                        case 'Error':
                            $job_error = $cptLog['cpt'];
                            break;
                        case 'Close':
                            $job_close = $cptLog['cpt'];
                            break;
						 case 'Cancel':
                            $job_cancel = $cptLog['cpt'];
                            break;
                    }
                }
            }

            $textMail = $this->tools->getTranslation(['email_notification', 'hello']) . '<br/>' . '<br/>' . $this->tools->getTranslation(['email_notification', 'introduction']) . '<br/>';
            $textMail .= $this->tools->getTranslation(['email_notification', 'transfer_success']) . ' ' . $job_close . '<br/>';
            $textMail .= $this->tools->getTranslation(['email_notification', 'transfer_error']) . ' ' . $job_error . '<br/>';
            $textMail .= $this->tools->getTranslation(['email_notification', 'transfer_open']) . ' ' . $job_open . '<br/>';
            $textMail .= $this->tools->getTranslation(['email_notification', 'transfer_cancel']) . ' ' . $job_cancel . '<br/>';

            // Récupération des règles actives
            $activeRules = $this->ruleRepository->findBy(['active' => true, 'deleted' => false]);
            if (!empty($activeRules)) {
                $textMail .= '<br/>' . $this->tools->getTranslation(['email_notification', 'active_rule']) . '<br/>';
                foreach ($activeRules as $activeRule) {
                    $textMail .= ' - ' . $activeRule->getName() . '<br/>';
                }
            } else {
                $textMail .= '<br/>' . $this->tools->getTranslation(['email_notification', 'no_active_rule']) . '<br/>';
            }

            // Add url if the parameter base_uri is defined in app\config\public
            if (!empty($this->configParams['base_uri'])) {
                $textMail .= '<br/>' . $this->configParams['base_uri'] . '<br/>';
            }
            // Create text
            $textMail .= '<br/>' . $this->tools->getTranslation(['email_notification', 'best_regards']) . '<br/>' . $this->tools->getTranslation(['email_notification', 'signature']);

            return $this->send($textMail, $this->tools->getTranslation(['email_notification', 'subject']));
        } catch (Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            $this->logger->error($error);
            throw new Exception($error);
        }
    }

	// Add every admin email in the notification list
    protected function setEmailAddresses()
    {
        $users = $this->userRepository->findEmailsToNotification();
        foreach ($users as $user) {
			if (!in_array($user['email'],$this->emailAddresses)) { 
				$this->emailAddresses[] = $user['email'];
			}
        }
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws Exception
     */
    public function resetPassword(User $user)
    {
        try {
            $this->setConfigParam();
            $template = $this->twig->load('Email/reset_password.html.twig');
            $content = $template->render([
                'user' => $user,
                'base_uri' => (!empty($this->configParams['base_uri']) ? $this->configParams['base_uri'] : ''),
            ]);

            if (!empty($_ENV['BREVO_APIKEY'])) {
                $config = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $_ENV['BREVO_APIKEY']);
                $apiInstance = new \Brevo\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $config);
                $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail();
                
                // Create sender object
                $sender = new \Brevo\Client\Model\SendSmtpEmailSender();
                $sender->setEmail($this->configParams['email_from'] ?? 'no-reply@myddleware.com');
                
                // Create recipient
                $recipient = new \Brevo\Client\Model\SendSmtpEmailTo();
                $recipient->setEmail($user->getEmail());
                
                // Set up the email
                $sendSmtpEmail->setSender($sender);
                $sendSmtpEmail->setTo([$recipient]);
                $sendSmtpEmail->setSubject($this->translator->trans('email_reset_password.subject'));
                $sendSmtpEmail->setHtmlContent($content);

                try {
                    $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
                } catch (Exception $e) {
                    throw new Exception('Exception when calling TransactionalEmailsApi->sendTransacEmail: '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
                }
            } else {
                $email = (new Email())
                    ->from($this->configParams['email_from'] ?? 'no-reply@myddleware.com')
                    ->to($user->getEmail())
                    ->subject($this->translator->trans('email_reset_password.subject'))
                    ->html($content);
                
                $this->mailer->send($email);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to send reset password email : '.$e->getMessage());
        }
    }

    // Get the content of the table config
    protected function setConfigParam()
    {
        if (empty($this->configParams)) {
            $configRepository = $this->entityManager->getRepository(Config::class);
            $configs = $configRepository->findAll();
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    $this->configParams[$config->getName()] = $config->getvalue();
                }
            }
        }
    }

    // Takes MAILER_URL and turns it into an array with all parameters
    public function envMailerUrlToArray(string $envString): array
    {
        $delimiters = ['?', '?encryption=', '&auth_mode=', '&username=', '&password='];
        $envStringQuestionMarks = str_replace($delimiters, $delimiters[0], $envString);
        $envArrayBeforeSplitHostPort = explode($delimiters[0], $envStringQuestionMarks);
        $noTsplitHostPort = $envArrayBeforeSplitHostPort[0];
        $splitHostPort = explode(':', $noTsplitHostPort);
        $port = $splitHostPort[2];
        $hostWithSlashes = $splitHostPort[1];
        $hostWithoutSlashes = substr($hostWithSlashes, 2);
        $hostAndPort = [$hostWithoutSlashes, $port];

        $removeFirstElement = array_shift($envArrayBeforeSplitHostPort);
        $envArray = array_merge($hostAndPort, $envArrayBeforeSplitHostPort);
        return $envArray;
    }
}
