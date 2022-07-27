<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LogoutController extends AbstractController
{
    /**
     * This is automatically detected by Symfony: when the user attempts to reach it,
     * they are automatically logged out and redirected to '/'.
     */
    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
    }
}
