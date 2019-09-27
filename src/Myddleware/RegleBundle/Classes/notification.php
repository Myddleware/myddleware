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

namespace Myddleware\RegleBundle\Classes;

use Symfony\Bridge\Monolog\Logger; // Gestion des logs
use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Accède aux services
use Doctrine\DBAL\Connection; // Connexion BDD
use Myddleware\RegleBundle\Classes\tools as MyddlewareTools; 

class notificationcore  {
		
	protected $em;
	protected $emailAddresses;
	protected $tools;

	public function __construct(Logger $logger, Container $container, Connection $dbalConnection) {				
		$this->logger = $logger; // gestion des logs symfony monolog
		$this->container = $container;
		$this->connection = $dbalConnection;
		$this->em = $this->container->get('doctrine')->getEntityManager();
		$this->tools = new MyddlewareTools($this->logger, $this->container, $this->connection);	
		$this->setEmailAddresses();
	}
	
	// Send alert if a job is running too long
	public function sendAlert() {
		try {
			$notificationParameter = $this->container->getParameter('notification');
			if (empty($notificationParameter['alert_time_limit'])) {
				throw new \Exception ('No alert time set in the parameters file. Please set the parameter alert_limit_minute in the file config/parameters.yml.');
			}
			// Calculate the date corresponding to the beginning still authorised
			$timeLimit = new \DateTime('now',new \DateTimeZone('GMT'));
			$timeLimit->modify('-'.$notificationParameter['alert_time_limit'].' minutes');
			
			// Search if a job is lasting more time that the limit authorized 	
			$sqlParams = "	SELECT * 
							FROM Job 
							WHERE 
									status = 'Start'
								AND begin < :timeLimit
							LIMIT 1";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->bindValue("timeLimit", $timeLimit->format('Y-m-d H:i:s'));
			$stmt->execute();	   				
			$job = $stmt->fetch();
	
			// If a job is found, we send the alert
			if(!empty($job)) {		
				// Create text
				$textMail = $this->tools->getTranslation(array('email_notification', 'hello')).chr(10).chr(10);
				$textMail .= $this->tools->getTranslation(array('email_alert', 'introduction')).' '.$notificationParameter['alert_time_limit'].' '.$this->tools->getTranslation(array('email_alert', 'minute')).chr(10).chr(10);
				$textMail .= $this->tools->getTranslation(array('email_alert', 'job_start')).' '.$job['begin'];
				$textMail .= $this->tools->getTranslation(array('email_alert', 'job_id')).' '.$job['id'].chr(10).chr(10);
				$textMail .= $this->tools->getTranslation(array('email_alert', 'recommandation')).chr(10).chr(10);				
				// Add url if the parameter base_uri is defined in app\config\public
				if (!empty($this->container->getParameter('base_uri'))) {
					$textMail .= chr(10).$this->container->getParameter('base_uri').chr(10);
				}
				$textMail .= $this->tools->getTranslation(array('email_notification', 'best_regards')).chr(10).$this->tools->getTranslation(array('email_notification', 'signature'));
				$message = \Swift_Message::newInstance()
					->setSubject($this->tools->getTranslation(array('email_alert', 'subject')))
					->setFrom('no-reply@myddleware.com')
					->setBody($textMail)
				;
				// Send the message to all admins
				foreach ($this->emailAddresses as $emailAddress) {
					$message->setTo($emailAddress);
					$send = $this->container->get('mailer')->send($message);
					if (!$send) {
						$this->logger->error('Failed to send alert email : '.$textMail.' to '.$contactMail);	
						throw new \Exception ('Failed to send alert email : '.$textMail.' to '.$contactMail);
					}			
				}
			}
			return true;
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			throw new \Exception ($error);							
		}
	}
	
	// Send notification to receive statistique about myddleware data transfer
	public function sendNotification() {
		try {
			// Check that we have at least one email address
			if (empty($this->emailAddresses)) {
				throw new \Exception ('No email address found to send notification. You should have at leas one admin user with an email address.');
			}
			
			// Write the introduction
			$textMail = $this->tools->getTranslation(array('email_notification', 'hello')).chr(10).chr(10).$this->tools->getTranslation(array('email_notification', 'introduction')).chr(10);

			// Récupération du nombre de données transférées depuis la dernière notification. On en compte qu'une fois les erreurs
			$sqlParams = "	SELECT
								count(distinct Log.doc_id) cpt,
								Document.global_status
							FROM Job
								INNER JOIN Log
									ON Log.job_id = Job.id
								INNER JOIN Rule
									ON Log.rule_id = Rule.id
								INNER JOIN Document
									 ON Document.id = Log.doc_id
									AND Document.deleted = 0
							WHERE
									Job.begin BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
								AND (
										Document.global_status != 'Error'
									OR (
											Document.global_status = 'Error'
										AND Document.date_modified BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
									)
								)
							GROUP BY Document.global_status";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->execute();	   				
			$cptLogs = $stmt->fetchAll();
			$job_open = 0;
			$job_close = 0;
			$job_cancel = 0;
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
						case 'Cancel':
							$job_cancel = $cptLog['cpt'];
							break;
					}
				}
			}			
			$textMail .= $this->tools->getTranslation(array('email_notification', 'transfer_success')).' '.$job_close.chr(10);
			$textMail .= $this->tools->getTranslation(array('email_notification', 'transfer_error')).' '.$job_error.chr(10);
			$textMail .= $this->tools->getTranslation(array('email_notification', 'transfer_open')).' '.$job_open.chr(10);	
			
			// Récupération des règles actives
			$sqlParams = "	SELECT * 
							FROM Rule
							WHERE
									Rule.active = 1
								AND	Rule.deleted = 0
			";
			$stmt = $this->connection->prepare($sqlParams);
			$stmt->execute();	   				
			$activeRules = $stmt->fetchAll();
			if (!empty($activeRules)) {
				$textMail .= chr(10).$this->tools->getTranslation(array('email_notification', 'active_rule')).chr(10);
				foreach ($activeRules as $activeRule) {
					$textMail .= " - ".$activeRule['name'].chr(10);
				}
			}
			else {
				$textMail .= chr(10).$this->tools->getTranslation(array('email_notification', 'no_active_rule')).chr(10);
			}
			
			// Get errors since the last notification
			if ($job_error > 0) {
				$sqlParams = "	SELECT
									Log.created,
									Log.msg,
									Log.doc_id,
									Rule.name
								FROM Job
									INNER JOIN Log
										ON Log.job_id = Job.id
									INNER JOIN Rule
										ON Log.rule_id = Rule.id
									INNER JOIN Document
										 ON Document.id = Log.doc_id
										AND Document.deleted = 0
								WHERE
										Job.begin BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
									AND Document.date_modified BETWEEN (SELECT MAX(begin) FROM Job WHERE param = 'notification' AND end >= begin) AND NOW()
									AND Document.global_status = 'Error'
									AND Log.type = 'E'
								ORDER BY Log.created ASC
								LIMIT 100	";
				$stmt = $this->connection->prepare($sqlParams);
				$stmt->execute();	   				
				$logs = $stmt->fetchAll();

				if (count($logs) == 100) {
					$textMail .= chr(10).chr(10).$this->tools->getTranslation(array('email_notification', '100_first_erros')).chr(10);
				}
				else  {
					$textMail .= chr(10).chr(10).$this->tools->getTranslation(array('email_notification', 'error_list')).chr(10);
				}
				foreach ($logs as $log) {
					$textMail .= " - Règle $log[name], id transfert $log[doc_id], le $log[created] : $log[msg]".chr(10);
				}
			}
			
			// Add url if the parameter base_uri is defined in app\config\public
			if (!empty($this->container->getParameter('base_uri'))) {
				$textMail .= chr(10).$this->container->getParameter('base_uri').chr(10);
			}
			// Create text
			$textMail .= chr(10).$this->tools->getTranslation(array('email_notification', 'best_regards')).chr(10).$this->tools->getTranslation(array('email_notification', 'signature'));
					
			$message = \Swift_Message::newInstance()
				->setSubject($this->tools->getTranslation(array('email_notification', 'subject')))
 				->setFrom('no-reply@myddleware.com')
				->setBody($textMail)
			;
			// Send the message to all admins
			foreach ($this->emailAddresses as $emailAddress) {
				$message->setTo($emailAddress);
				$send = $this->container->get('mailer')->send($message);
				if (!$send) {
					$this->logger->error('Failed to send email : '.$textMail.' to '.$contactMail);	
					throw new \Exception ('Failed to send email : '.$textMail.' to '.$contactMail);
				}			
			}
			return true;
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			throw new \Exception ($error);							
		}
	}
	
	// Add every admin email in the notification list
	protected function setEmailAddresses() {
		$this->emailAddresses = array();
		$sqlParams = "	SELECT * FROM users WHERE enabled = 1";
		$stmt = $this->connection->prepare($sqlParams);
		$stmt->execute();	   				
		$users = $stmt->fetchAll();
		if(!empty($users)) {		
			foreach ($users as $user) {
				if (strpos($user['roles'],'ROLE_ADMIN')!==false) {
					$this->emailAddresses[] = $user['email'];
				}
			}
		}
	}
	
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/notification.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class notification extends notificationcore {
		
	}
}
?>