<?php

namespace App\Controller;

use App\Service\DebugLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LogoutController extends AbstractController
{
    private DebugLogger $debugLogger;

    public function __construct(DebugLogger $debugLogger)
    {
        $this->debugLogger = $debugLogger;
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on the firewall
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
            throw new \LogicException('This method should not be reached!');
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }
}
