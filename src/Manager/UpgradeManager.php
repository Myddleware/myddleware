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
use App\Entity\RuleRelationShip;
use Psr\Log\LoggerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\Service\DebugLogger;

class UpgradeManager
{
    private DebugLogger $debugLogger;
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
        EntityManagerInterface $entityManager,
        DebugLogger $debugLogger
    ) {
        $this->debugLogger = $debugLogger;
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
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['output' => $output]);
        $__debugReturn = null;
        try {
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

        return $__debugReturn = $this->message;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * @throws Exception
     */
    protected function updateFiles()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
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
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    // Update vendors via composer
    protected function updateVendors()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
        // Change the command composer if php isn't the default php version
        $process = new Process(['composer', 'install', '--ignore-platform-reqs']);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    // Execute yarn action
    protected function yarnAction()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
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
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    // Clear boostrap cache
    protected function clearBoostrapCache()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
        $process = new Process(array($this->phpExecutable.' vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php'));
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    /**
     * @throws Exception
     */
    protected function updateDatabase()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        // The files and vendors have just been updated but this process still runs the previous version
        // of Myddleware (classes and container already loaded). The database commands are therefore run
        // in a new PHP process, so that they use the new code (entities mapping, Doctrine migrations...),
        // with a temporary cache directory so that the previous compiled container is not reused.
        $temporaryCacheDir = $this->projectDir.'/var/cache/upgrade';
        $processEnv = ['APP_CACHE_DIR' => $temporaryCacheDir];
        try {
            // Doctrine migrations first: they convert the data that the schema update can't handle on its own
            // (e.g. upgrade to 4.4: serialized PHP "array" columns converted to JSON)
            if (!empty(glob($this->projectDir.'/migrations/Version*.php'))) {
                $this->runConsoleCommand(['doctrine:migrations:migrate', '--allow-no-migration'], $processEnv);
            }

            // Update schema
            $this->runConsoleCommand(['doctrine:schema:update', '--force', '--complete'], $processEnv);

            // Update data
            $this->runConsoleCommand(['doctrine:fixtures:load', '--append'], $processEnv);
        } finally {
            (new Filesystem())->remove($temporaryCacheDir);
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    /**
     * Run a Symfony console command in a new PHP process (i.e. with the code currently on the disk).
     *
     * @throws Exception
     */
    protected function runConsoleCommand(array $arguments, array $processEnv = []): void
    {
        $process = new Process(
            array_merge([$this->phpExecutable, 'bin/console'], $arguments, ['--env='.$this->env, '--no-interaction']),
            $this->projectDir,
            $processEnv,
            null,
            null // No timeout: the data migration can be long on big databases
        );
        // Display the output in real time (progress of long commands)
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        $content = trim($process->getOutput().chr(10).$process->getErrorOutput());
        if (!empty($content)) {
            $this->logger->info($content);
            $this->message .= $content.chr(10);
        }
        if (!$process->isSuccessful()) {
            throw new Exception('The command "'.implode(' ', $arguments).'" failed (exit code '.$process->getExitCode().'). '.$content);
        }
    }



    /**
     * @throws Exception
     */
    protected function clearSymfonyCache()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
        $fs = new Filesystem();
        // Add current environment to the default list
        $this->defaultEnvironment[$this->env] = $this->env;

        // The cache of every environment is removed: it has been compiled with the previous version of Myddleware.
        // It can't be cleared with the command cache:clear in this process because this process still runs
        // the previous version of the code.
        foreach ($this->defaultEnvironment as $env) {
            try {
                $fs->remove($this->projectDir.'/var/cache/'.$env);
            } catch (IOExceptionInterface $exception) {
                echo "An error occurred while clearing your directory at ".$exception->getPath();
                $this->logger->error("An error occurred while clearing your directory at ".$exception->getPath());
                throw new \Exception("An error occurred while clearing your directory at ".$exception->getPath());
            }
            $this->logger->info("Cache cleared for environment: ".$env);
            $this->message .= "Cache cleared for environment: ".$env.chr(10);
        }

        // Rebuild the cache of the current environment with the new code
        $this->runConsoleCommand(['cache:warmup']);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }


    // Get the content of the table config
    protected function setConfigParam()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
        $configRepository = $this->entityManager->getRepository(Config::class);
        $configs = $configRepository->findAll();
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->configParams[$config->getName()] = $config->getvalue();
            }
        }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    // Function to customize the update process
    protected function beforeUpdate($output)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['output' => $output]);
        try {
        // Check for active relationships before upgrade
        $activeRelationships = $this->entityManager
            ->getRepository(RuleRelationShip::class)
            ->createQueryBuilder('rr')
            ->select('COUNT(rr.id)')
            ->where('rr.deleted = :deleted')
            ->setParameter('deleted', false)
            ->getQuery()
            ->getSingleScalarResult();

        if ($activeRelationships > 0) {
            throw new \Exception(sprintf(
                'Impossible de procéder à l\'upgrade : %d relation(s) active(s) détectée(s) dans la table rulerelationship. ' .
                'Veuillez supprimer ou désactiver toutes les relations avant de lancer l\'upgrade.',
                $activeRelationships
            ));
        }

        $output->writeln('<info>Vérification des relations : OK (aucune relation active)</info>');
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    // Function to customize the update process
    protected function afterUpdate($output)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['output' => $output]);
        try {
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }
}
