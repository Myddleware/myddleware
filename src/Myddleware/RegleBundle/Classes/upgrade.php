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

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;

class upgradecore  {
		
	protected $env;
	protected $em;
	protected $newParameters;
	protected $currentParameters;
	protected $phpExecutable = 'php';
	protected $message = '';
	protected $defaultEnvironment = array('prod'=>'prod','background'=>'background');

	public function __construct(Logger $logger, Container $container, Connection $dbalConnection) {				
		$this->logger = $logger; // gestion des logs symfony monolog
		$this->container = $container;
		$this->connection = $dbalConnection;
		$this->env = $this->container->getParameter("kernel.environment");
		$this->em = $this->container->get('doctrine')->getEntityManager();
		
		// New parameters in file parameters.yml.dist
		$this->newParameters['parameters'] = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml.dist'));	
		$this->newParameters['parameters_public'] = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/public/parameters_public.yml.dist'));	
		$this->newParameters['parameters_smtp'] = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/public/parameters_smtp.yml.dist'));	
		// Current parameters in file parameters.yml
		$this->currentParameters['parameters'] =  \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml'));	
		$this->currentParameters['parameters_public'] = '';
		if (file_exists($this->container->getParameter('kernel.root_dir').'/config/public/parameters_public.yml')){
			$this->currentParameters['parameters_public'] =  \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/public/parameters_public.yml'));	
		}
		$this->currentParameters['parameters_smtp'] = '';
		if (file_exists($this->container->getParameter('kernel.root_dir').'/config/public/parameters_smtp.yml')){
			$this->currentParameters['parameters_smtp'] =  \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/public/parameters_smtp.yml'));
		}
		
		// Get php executable 
		$phpParameter = $this->container->getParameter('php');
		if (!empty($phpParameter['executable'])) {
			$this->phpExecutable = $phpParameter['executable'];
		}
	}
	
	public function processUpgrade($output) {
		try{
			// Customize update process
			$this->beforeUpdate($output);
			
		 	// Add new parameters
			$output->writeln('<comment>Update parameters...</comment>');
			$this->updateParameters();
			$output->writeln('<comment>Update parameters OK</comment>');
			$this->message .= 'Update parameters OK'.chr(10);
		
			// Update file
			$output->writeln('<comment>Update files...</comment>');
			$this->updateFiles();
			$output->writeln('<comment>Update files OK</comment>');
			$this->message .= 'Update files OK'.chr(10);
			
			// Update vendor via composer
			$output->writeln('<comment>Update vendors...</comment>');
			$this->updateVendors();
			$output->writeln('<comment>Update vendors OK</comment>');
			$this->message .= 'Update vendors OK'.chr(10);
			
			/* // Clear boostrap cache
			$output->writeln('<comment>Clear boostrap cache...</comment>');
			$this->clearBoostrapCache();
			$output->writeln('<comment>Clear boostrap cache OK</comment>'); */
			 
			// Update database
			$output->writeln('<comment>Update database...</comment>');
			$this->updateDatabase();
			$output->writeln('<comment>Update database OK</comment>');
			$this->message .= 'Update database OK'.chr(10);
			
			// Change Myddleware version
			$output->writeln('<comment>Finish install...</comment>');
			$this->finishInstall();
			$output->writeln('<comment>Finish install OK</comment>');
			$this->message .= 'Finish install OK'.chr(10);
			
			// Clear cache
			$output->writeln('<comment>Clear Symfony cache...</comment>');
			$this->clearSymfonycache();
			$output->writeln('<comment>Clear Symfony cache OK</comment>');
			$this->message .= 'Clear Symfony cache OK'.chr(10);
			
			// Change Myddleware version
			$output->writeln('<comment>Update version...</comment>');
			$this->changeVersion();
			$output->writeln('<comment>Update version OK</comment>');
			$this->message .= 'Update version OK'.chr(10);
					
			// Customize update process
			$this->afterUpdate($output);
			
			$output->writeln('<info>Myddleware has been successfully updated in version '.$this->newParameters['parameters']['parameters']['myd_version'].'</info>');
			$this->message .= 'Myddleware has been successfully updated in version '.$this->newParameters['parameters']['parameters']['myd_version'].chr(10);
		
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			$this->message .= $error.chr(10);
			$output->writeln('<error>'.$error.'</error>');
		}	
		return $this->message;
	}
	
	// Update parameters with dist file
	protected function updateParameters() {
		// Foreach parameter file
		foreach($this->newParameters as $key => $yml) {
			// Check if a parameter exists in dist file and not in the parameter file
			foreach ($yml['parameters'] as $newParameterKey => $newParameterValue) {		
				if (		
						empty ($this->currentParameters[$key]['parameters'])
					 OR array_key_exists($newParameterKey, $this->currentParameters[$key]['parameters'])===false
				) {				
					// Add it i the parameter file
					$this->currentParameters[$key]['parameters'][$newParameterKey] = $newParameterValue;
					$new_yaml = \Symfony\Component\Yaml\Yaml::dump($this->currentParameters[$key], 4);
					file_put_contents($this->container->getParameter('kernel.root_dir').'/config/'.($key == 'parameters' ? '' : 'public/').$key.'.yml', $new_yaml);
					$info = 'New parameter '.$newParameterKey.' added to the file config/'.($key == 'parameters' ? '' : 'public/').$key.'.yml';
					echo $info.chr(10);
					$this->logger->info($info);
					$this->message .= $info.chr(10);
				}
			}
		}
	}
	
	protected function updateFiles() {		
		// Update master if git_branch is empty otherwise we update the specific branch
		$command = (!empty($this->container->getParameter('git_branch'))) ? 'git pull origin '.$this->container->getParameter('git_branch') : 'git pull';
		$process = new Process($command);
		$process->run();
		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
		$output1 = $process->getOutput();
		if (strpos($output1, 'Aborting') !== false) {
			echo $process->getOutput();
			$this->logger->error($process->getOutput());
			$this->message .= $process->getOutput().chr(10);
			throw new \Exception ('Failed to update Myddleware. Failed to update Myddleware files by using git');
		}
		
		// Run the command a second time, we expect to get the message "Already up-to-date"
		$process = new Process($command);
		$process->run();
		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
		$output2 = $process->getOutput();
		echo $output2;
		$this->message .= $output2.chr(10);
		if (
				strpos($output2, 'Already up to date') === false
			AND	strpos($output2, 'Already up-to-date') === false
		) {
			throw new \Exception ('Failed to update Myddleware. Files are not up-to-date.');
		}
	}
	
	// Update vendors via composer
	protected function updateVendors() {		
		$process = new Process($this->phpExecutable.' composer.phar install --no-plugins');
		$process->run();
		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
	}
	
	// Clear boostrap cache
	protected function clearBoostrapCache() {		
		$process = new Process($this->phpExecutable.' vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php');
		$process->run();
		// executes after the command finishes
		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
	}
	
	// Update database
	protected function updateDatabase() {
		// Update schema
		$application = new Application($this->container->get('kernel'));
		$application->setAutoExit(false);
		$arguments = array(
			'command' => 'doctrine:schema:update',
			'--force' => true,
			'--env' => $this->env,
		);
		
		$input = new ArrayInput($arguments);
		$output = new BufferedOutput();
		$application->run($input, $output);

		$content = $output->fetch();
		if (!empty($content)) {
			echo $content.chr(10);
			$this->logger->info($content);
			$this->message .= $content.chr(10);
		}

		// Update data
		$argumentsFixtures = array(
			'command' => 'doctrine:fixtures:load',
			'--append' => true,
			'--env' => $this->env,
		);
		
		$input = new ArrayInput($argumentsFixtures);
		$output = new BufferedOutput();
		$application->run($input, $output);

		$content = $output->fetch();
		// Send output to the logfile if debug mode selected
		if (!empty($content)) {
			echo $content.chr(10);
			$this->logger->info($content);
			$this->message .= $content.chr(10);
		}
	}
	
	// Clear Symfony cache
	protected function clearSymfonycache() {
		// Add current environement  to the default list		
		$this->defaultEnvironment[$this->env] = $this->env;	
		
		foreach ($this->defaultEnvironment as $env) {
			// Command clear cach remove only current environment cache
			if ($this->env == $env) {
				// Clear cache
				$application = new Application($this->container->get('kernel'));
				$application->setAutoExit(false);
				$arguments = array(
					'command' => 'cache:clear',
					'--env' => $env,
				);	
				
				$input = new ArrayInput($arguments);
				$output = new BufferedOutput();
				$application->run($input, $output);

				$content = $output->fetch();
				// Send output to the logfile if debug mode selected
				if (!empty($content)) {
					echo $content.chr(10);
					$this->logger->info($content);
					$this->message .= $content.chr(10);
				}
			} else {
				// CLear other environment cache via command
				$command = 'rm -rf var/cache/'.$env.'/*';
				$process = new Process($command);
				$process->run();
				// executes after the command finishes
				if (!$process->isSuccessful()) {
					throw new ProcessFailedException($process);
				}
				echo $process->getOutput();
				$this->logger->error($process->getOutput());
				$this->message .= $process->getOutput().chr(10);				
			}
		} 
		
		// Refresh new parameters in file parameters.yml.dist
		$this->newParameters['parameters'] = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml.dist'));	
		$this->newParameters['parameters_public'] = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/public/parameters_public.yml.dist'));	
		$this->newParameters['parameters_smtp'] = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/public/parameters_smtp.yml.dist'));	
		
	}
	
	// Finish install
	protected function finishInstall() {
		// Update schema
		$application = new Application($this->container->get('kernel'));
		$application->setAutoExit(false);
		$arguments = array(
			'command' => 'assetic:dump',
			'--env' => $this->env,
		);
		
		$input = new ArrayInput($arguments);
		$output = new BufferedOutput();
		$application->run($input, $output);

		$content = $output->fetch();
		// Send output to the logfile if debug mode selected
		if (!empty($content)) {
			echo $content.chr(10);
			$this->logger->info($content);
			$this->message .= $content.chr(10);
		}

		// Update data
		$argumentsFixtures = array(
			'command' => 'assets:install',
			'--env' => $this->env,
		);
		
		$input = new ArrayInput($argumentsFixtures);
		$output = new BufferedOutput();
		$application->run($input, $output);

		$content = $output->fetch();
		// Send output to the logfile if debug mode selected
		if (!empty($content)) {
			echo $content.chr(10);
			$this->logger->info($content);
			$this->message .= $content.chr(10);
		}
	}
	
	// Myddleware upgrade
	protected function changeVersion() {
		// Read the file parameters.yml.dist with the new version of Myddleware		
		if (!empty($this->newParameters['parameters']['parameters']['myd_version'])) {
			if ($this->newParameters['parameters']['parameters']['myd_version'] != $this->currentParameters['parameters']['parameters']['myd_version']) {
				$this->currentParameters['parameters']['parameters']['myd_version'] = $this->newParameters['parameters']['parameters']['myd_version'];
				$new_yaml = \Symfony\Component\Yaml\Yaml::dump($this->currentParameters['parameters'], 4);
				file_put_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml', $new_yaml);
				$info = 'Version changed to '.$this->newParameters['parameters']['parameters']['myd_version'].' in the file /config/parameters.yml';
				echo $info.chr(10);
				$this->logger->info($info);
				$this->message .= $info.chr(10);
			}
		} else {
			throw new \Exception ('No version in the file parameters.yml.dist. Failed to update the version of Myddleware.');
		}		
	}	
	
	// Function to customize the update process
	protected function beforeUpdate($output) {
	}
	
	// Function to customize the update process
	protected function afterUpdate($output) {
	}
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/upgrade.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class upgrade extends upgradecore {
		
	}
}
?>