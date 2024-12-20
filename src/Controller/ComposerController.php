<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class ComposerController extends AbstractController
{
    #[Route('/run-composer-install', name: 'run_composer_install', methods: ['POST'])]
    public function runComposerInstall(): JsonResponse
    {
        try {
            $process = new Process(['composer', 'install']);
            $process->setWorkingDirectory($this->getParameter('kernel.project_dir'));
            
            // Set required environment variables
            $env = array_merge($_SERVER, [
                'APPDATA' => getenv('APPDATA'),
                'COMPOSER_HOME' => getenv('APPDATA') . '/Composer',
                'SystemRoot' => getenv('SystemRoot'),
                'TEMP' => getenv('TEMP'),
                'TMP' => getenv('TMP')
            ]);
            
            $process->setEnv($env);
            $process->setTimeout(3600); // Set timeout to 1 hour
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Composer install completed successfully',
                'output' => $process->getOutput()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error running composer install: ' . $e->getMessage(),
                'output' => $process->getErrorOutput() ?? null
            ], 500);
        }
    }
} 