<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TerminalController extends AbstractController
{
    #[Route('/open-terminal', name: 'open_terminal')]
    public function openTerminal(): JsonResponse
    {
        $projectRoot = $this->getParameter('kernel.project_dir');
        
        if (PHP_OS_FAMILY === 'Linux') {
            exec('gnome-terminal --working-directory="' . $projectRoot . '" > /dev/null 2>&1 &');
            // or for KDE: exec('konsole --workdir="' . $projectRoot . '" > /dev/null 2>&1 &');
            // or for XFCE: exec('xfce4-terminal --working-directory="' . $projectRoot . '" > /dev/null 2>&1 &');
        } elseif (PHP_OS_FAMILY === 'Darwin') {  // macOS
            exec('open -a Terminal "' . $projectRoot . '"');
        } elseif (PHP_OS_FAMILY === 'Windows') {
            exec('start cmd /K "cd /d ' . $projectRoot . '"');
        }

        return new JsonResponse(['success' => true]);
    }
}
