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

use App\Entity\User;
use App\Repository\JobRepository;
use App\Repository\RuleRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

$file = __DIR__.'/../Custom/Manager/NotificationManager.php';
if (file_exists($file)) {
    require_once $file;
} else {
    /**
     * Class NotificationManager.
     *
     * @package App\Manager
     *
     *
     */
    class NotificationManager
    {
        protected $entityManager;
        protected $emailAddresses;
        protected $tools;
        /**
         * @var LoggerInterface
         */
        private $logger;
        /**
         * @var ParameterBagInterface
         */
        private $params;
        /**
         * @var Connection
         */
        private $connection;
        /**
         * @var Swift_Mailer
         */
        private $mailer;
        /**
         * @var UserRepository
         */
        private $userRepository;
        /**
         * @var TranslatorInterface
         */
        private $translator;
        /**
         * @var JobRepository
         */
        private $jobRepository;
        /**
         * @var RuleRepository
         */
        private $ruleRepository;
        /**
         * @var mixed|string
         */
        private $fromEmail;
        /**
         * @var Environment
         */
        private $twig;

        public function __construct(
            LoggerInterface $logger,
            Connection $connection,
            EntityManagerInterface $entityManager,
            TranslatorInterface $translator,
            UserRepository $userRepository,
            JobRepository $jobRepository,
            RuleRepository $ruleRepository,
            Swift_Mailer $mailer,
            ToolsManager $tools,
            ParameterBagInterface $params,
            Environment $twig
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
            $this->setEmailAddresses();
            $this->fromEmail = $this->params->get('email_from') ?? 'no-reply@myddleware.com';
        }

        // Send alert if a job is running too long
        public function sendAlert()
        {
            try {
                $notificationParameter = $this->params->get('notification');
                if (empty($notificationParameter['alert_time_limit'])) {
                    throw new Exception('No alert time set in the parameters file. Please set the parameter alert_limit_minute in the file config/parameters.yml.');
                }
                // Calculate the date corresponding to the beginning still authorised
                $timeLimit = new DateTime('now', new \DateTimeZone('GMT'));
                $timeLimit->modify('-'.$notificationParameter['alert_time_limit'].' minutes');

                // Search if a job is lasting more time that the limit authorized
                $job = $this->jobRepository->findJobStarted($timeLimit);
                // If a job is found, we send the alert
                if (!$job) {
                    // Create text
                    $textMail = $this->translator->trans('email_alert.body', [
                        '%min%' => $notificationParameter['alert_time_limit'],
                        '%begin%' => $job['begin'],
                        '%id%' => $job['id'],
                        'base_uri' => $this->params->get('base_uri') ?? '',
                    ]);

                    $message =
                        (new Swift_Message($this->translator->trans('email_alert.subject')))
                        ->setFrom($this->fromEmail)
                        ->setBody($textMail);
                    // Send the message to all admins
                    foreach ($this->emailAddresses as $emailAddress) {
                        $message->setTo($emailAddress);
                        $send = $this->mailer->send($message);
                        if (!$send) {
                            $this->logger->error('Failed to send alert email : '.$textMail.' to '.$emailAddress);
                            throw new Exception('Failed to send alert email : '.$textMail.' to '.$emailAddress);
                        }
                    }
                }

                return true;
            } catch (Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($error);
                throw new Exception($error);
            }
        }

        // Send notification to receive statistique about myddleware data transfer
        public function sendNotification()
        {
            try {
                // Check that we have at least one email address
                if (empty($this->emailAddresses)) {
                    throw new Exception('No email address found to send notification. You should have at leas one admin user with an email address.');
                }

                // Récupération du nombre de données transférées depuis la dernière notification. On en compte qu'une fois les erreurs
                $cptLogs = $this->jobRepository->getLogStatistique();
                $job_open = 0;
                $job_close = 0;
                $job_error = 0;
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
                        }
                    }
                }

                $textMail = $this->tools->getTranslation(['email_notification', 'hello']).chr(10).chr(10).$this->tools->getTranslation(['email_notification', 'introduction']).chr(10);
                $textMail .= $this->tools->getTranslation(['email_notification', 'transfer_success']).' '.$job_close.chr(10);
                $textMail .= $this->tools->getTranslation(['email_notification', 'transfer_error']).' '.$job_error.chr(10);
                $textMail .= $this->tools->getTranslation(['email_notification', 'transfer_open']).' '.$job_open.chr(10);

                // Récupération des règles actives
                $activeRules = $this->ruleRepository->findBy(['active' => true, 'deleted' => false]);
                if (!empty($activeRules)) {
                    $textMail .= chr(10).$this->tools->getTranslation(['email_notification', 'active_rule']).chr(10);
                    foreach ($activeRules as $activeRule) {
                        $textMail .= ' - '.$activeRule['name'].chr(10);
                    }
                } else {
                    $textMail .= chr(10).$this->tools->getTranslation(['email_notification', 'no_active_rule']).chr(10);
                }

                // Get errors since the last notification
                if ($job_error > 0) {
                    $logs = $this->jobRepository->getErrorsSinceLastNotification();
                    if (100 == count($logs)) {
                        $textMail .= chr(10).chr(10).$this->tools->getTranslation(['email_notification', '100_first_erros']).chr(10);
                    } else {
                        $textMail .= chr(10).chr(10).$this->tools->getTranslation(['email_notification', 'error_list']).chr(10);
                    }
                    foreach ($logs as $log) {
                        $textMail .= " - Règle $log[name], id transfert $log[id], le $log[begin] : $log[message]".chr(10);
                    }
                }

                // Add url if the parameter base_uri is defined in app\config\public
                if (!empty($this->params->get('base_uri'))) {
                    $textMail .= chr(10).$this->params->get('base_uri').chr(10);
                }
                // Create text
                $textMail .= chr(10).$this->tools->getTranslation(['email_notification', 'best_regards']).chr(10).$this->tools->getTranslation(['email_notification', 'signature']);

                $message = Swift_Message::newInstance()
                    ->setSubject($this->tools->getTranslation(['email_notification', 'subject']))
                    ->setFrom((!empty($this->params->get('email_from')) ? $this->params->get('email_from') : 'no-reply@myddleware.com'))
                    ->setBody($textMail);
                // Send the message to all admins
                foreach ($this->emailAddresses as $emailAddress) {
                    $message->setTo($emailAddress);
                    $send = $this->mailer->send($message);
                    if (!$send) {
                        $this->logger->error('Failed to send email : '.$textMail.' to '.$emailAddress);
                        throw new Exception('Failed to send email : '.$textMail.' to '.$emailAddress);
                    }
                }

                return true;
            } catch (Exception $e) {
                $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
                $this->logger->error($error);
                throw new Exception($error);
            }
        }

        // Add every admin email in the notification list
        private function setEmailAddresses()
        {
            $users = $this->userRepository->findEmailsToNotification();
            foreach ($users as $user) {
                $this->emailAddresses[] = $user['email'];
            }
        }

        public function resetPassword(User $user)
        {
            $message = (new Swift_Message('Initialisation du mot de passe'))
                ->setFrom($this->fromEmail)
                ->setTo($user->getEmail())
                ->setBody($this->twig->render('Email/reset_password.html.twig', ['user' => $user]));

            $send = $this->mailer->send($message);
            if (!$send) {
                $this->logger->error('Failed to send email');
                throw new Exception('Failed to send email');
            }
        }
    }
}
