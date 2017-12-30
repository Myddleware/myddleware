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

class upgradecore  {
		
	protected $env;
	protected $em;
	protected $newParameters;
	protected $currentParameters;

	public function __construct(Logger $logger, Container $container, Connection $dbalConnection) {				
		$this->logger = $logger; // gestion des logs symfony monolog
		$this->container = $container;
		$this->connection = $dbalConnection;
		$this->env = $this->container->getParameter("kernel.environment");
		$this->em = $this->container->get('doctrine')->getEntityManager();
		
		// New parameters in file parameters.yml.dist
		$this->newParameters = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml.dist'));	
		// Current parameters in file parameters.yml
		$this->currentParameters =  \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml'));	
	}
	
	public function processUpgrade() {
		try{
			// Customize update process
			$this->beforeUpdate();
			
			// Add new parameters
			$this->updateParameters();
			
			// Change Myddleware version
			$this->changeVersion();
			
			// Customize update process
			$this->afterUpdate();
			
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			$this->logger->error($error);
			// Error are displayed in the command
			throw new \Exception($error);
		}	
	}
	
	// Update parameters with dist file
	protected function updateParameters() {
		// Check if a parameter exists in dist file and not in the parameter file
		foreach ($this->newParameters['parameters'] as $newParameterKey => $newParameterValue) {
			if(array_key_exists($newParameterKey, $this->currentParameters['parameters'])===false) {
				// Add it i the parameter file
				$this->currentParameters['parameters'][$newParameterKey] = $newParameterValue;
				$new_yaml = \Symfony\Component\Yaml\Yaml::dump($this->currentParameters, 4);
				file_put_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml', $new_yaml);
				$info = 'New parameter '.$newParameterKey.' added to the file /config/parameters.yml';
				echo $info.chr(10);
				$this->logger->info($info);
			}
		}
	}
	
	// Myddleware upgrade
	protected function changeVersion() {
		// Read the file parameters.yml.dist with the new version of Myddleware		
		if (!empty($this->newParameters['parameters']['myd_version'])) {
			if ($this->newParameters['parameters']['myd_version'] != $this->currentParameters['parameters']['myd_version']) {
				$this->currentParameters['parameters']['myd_version'] = $this->newParameters['parameters']['myd_version'];
				$new_yaml = \Symfony\Component\Yaml\Yaml::dump($this->currentParameters, 4);
				file_put_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml', $new_yaml);
				$info = 'Version changed to '.$this->newParameters['parameters']['myd_version'].' in the file /config/parameters.yml';
				echo $info.chr(10);
				$this->logger->info($info);
			}
		} else {
			throw new \Exception ('No version in the file parameters.yml.dist. Failed to update the version of Myddleware.');
		}		
	}	
	
	
	// Function to customize the update process
	protected function beforeUpdate() {
	}
	
	// Function to customize the update process
	protected function afterUpdate() {
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