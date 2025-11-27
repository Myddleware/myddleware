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

use Exception;
use App\Entity\Config;
use Psr\Log\LoggerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class UpgradeManager
{
    protected string $env;
    protected $em;
    protected $phpExecutable = 'php';
    protected string $message = '';
    protected array $defaultEnvironment = ['prod' => 'prod', 'background' => 'background'];
    protected $configParams;
    private LoggerInterface $logger;
    private string $projectDir;
    private KernelInterface $kernel;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        KernelInterface $kernel,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->kernel = $kernel;
        $this->entityManager = $entityManager;
        $this->env = $kernel->getEnvironment();
        $this->projectDir = $kernel->getProjectDir();

        // Get the php executable
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();
        $this->phpExecutable = $phpBinaryPath;
    }

    public function processUpgrade($output): string
    {
        try {

            $envFilePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
            $dotenv = new Dotenv();
    
            if (file_exists($envFilePath)) {
                $dotenv->load($envFilePath);
            }
            $oldVersion = $_ENV['MYDDLEWARE_VERSION'];

            // Customize update process
            $this->beforeUpdate($output);
            // Set all config parameters
            $this->setConfigParam();

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

            // Update database
            $output->writeln('<comment>Update database...</comment>');
            $this->updateDatabase();
            $output->writeln('<comment>Update database OK</comment>');
            $this->message .= 'Update database OK'.chr(10);

            // Clear cache
            $output->writeln('<comment>Clear Symfony cache...</comment>');
            $this->clearSymfonyCache();
            $output->writeln('<comment>Clear Symfony cache OK</comment>');
            $this->message .= 'Clear Symfony cache OK'.chr(10);

            // Yarn action
            $output->writeln('<comment>Yarn action... Can take 1 or 2 minutes </comment>');
            $this->yarnAction();
            $output->writeln('<comment>Yarn action OK</comment>');
            $this->message .= 'Yarn action OK'.chr(10);

            // Customize update process
            $this->afterUpdate($output);

            // Refresh variable from env file
            if (file_exists($envFilePath)) {
                (new Dotenv())->load($envFilePath);
            }

            $newVersion = $_ENV['MYDDLEWARE_VERSION'];



            $output->writeln('<info>Myddleware has been successfully updated from version '.$oldVersion.' to '.$newVersion.'</info>');
            $this->message .= 'Myddleware has been successfully updated from version '.$oldVersion.' to '.$newVersion.chr(10);
        } catch (Exception $e) {
            $error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
            $this->logger->error($error);
            $this->message .= $error.chr(10);
            $output->writeln('<error>'.$error.'</error>');
        }

        return $this->message;
    }

    /**
     * @throws Exception
     */
    protected function updateFiles()
    {
        // Update Main if git_branch is empty otherwise we update the specific branch
        $command = (!empty($this->configParams['git_branch'])) ? ['git', 'pull', 'origin', $this->configParams['git_branch']] : ['git', 'pull'];
        $process = new Process($command);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $output1 = $process->getOutput();
        if (false !== strpos($output1, 'Aborting')) {
            echo $process->getOutput();
            $this->logger->error($process->getOutput());
            $this->message .= $process->getOutput().chr(10);
            throw new Exception('Failed to update Myddleware. Failed to update Myddleware files by using git');
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
            false === strpos($output2, 'Already up to date')
            and false === strpos($output2, 'Already up-to-date')
        ) {
            throw new Exception('Failed to update Myddleware. Files are not up-to-date.');
        }
    }

    // Update vendors via composer
    protected function updateVendors()
    {
        // Change the command composer if php isn't the default php version
        $process = new Process(['composer', 'install', '--ignore-platform-reqs']);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    // Execute yarn action
    protected function yarnAction()
    {
        $process = new Process(['yarn',  'install']);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $process = new Process(['yarn', 'build']);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    // Clear boostrap cache
    protected function clearBoostrapCache()
    {
        $process = new Process(array($this->phpExecutable.' vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php'));
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * @throws Exception
     */
    protected function updateDatabase()
    {
        // Update schema
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $arguments = [
            'command' => 'doctrine:schema:update',
            '--force' => true,
            '--complete' => true,
            '--env' => $this->env,
        ];

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
        $argumentsFixtures = [
            'command' => 'doctrine:fixtures:load',
            '--append' => true,
            '--env' => $this->env,
        ];

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

    
    
    /**
     * @throws Exception
     */
    protected function clearSymfonyCache()
    {
        $fs = new Filesystem();
        // Add current environment to the default list
        $this->defaultEnvironment[$this->env] = $this->env;
    
        foreach ($this->defaultEnvironment as $env) {
            // Command clear cache remove only current environment cache
            if ($this->env == $env) {
                // Clear cache
                $application = new Application($this->kernel);
                $application->setAutoExit(false);
                $arguments = [
                    'command' => 'cache:clear',
                    '--env' => $env,
                ];
    
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
                // Clear other environment cache via command
                try {
                    $fs->remove('var/cache/'.$env.'/*');
                } catch (IOExceptionInterface $exception) {
                    echo "An error occurred while clearing your directory at ".$exception->getPath();
                    $this->logger->error("An error occurred while clearing your directory at ".$exception->getPath());
                    throw new \Exception("An error occurred while clearing your directory at ".$exception->getPath());
                }
                
                $this->logger->info("Cache cleared for environment: ".$env);
                $this->message .= "Cache cleared for environment: ".$env.chr(10);
            }
        }
    }
    

    // Get the content of the table config
    protected function setConfigParam()
    {
        $configRepository = $this->entityManager->getRepository(Config::class);
        $configs = $configRepository->findAll();
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->configParams[$config->getName()] = $config->getvalue();
            }
        }
    }

    // Function to customize the update process
    protected function beforeUpdate($output)
    {
    }

    // Function to customize the update process
    protected function afterUpdate($output)
    {
    }
}
