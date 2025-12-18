<?php

/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/
namespace App\Controller;

use App\Entity\Solution;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Repository\RuleRepository;
use App\Service\TwoFactorAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private TwoFactorAuthService $twoFactorAuthService;
    private DocumentRepository $documentRepository;
    private RuleRepository $ruleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        TwoFactorAuthService $twoFactorAuthService,
        DocumentRepository $documentRepository,
        RuleRepository $ruleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->twoFactorAuthService = $twoFactorAuthService;
        $this->documentRepository = $documentRepository;
        $this->ruleRepository = $ruleRepository;
    }

    protected function getInstanceBdd()
    {
    }
    /**
     * TABLEAU DE BORD.
     */
    #[Route('/rule/panel', name: 'regle_panel')]
    public function panel(Request $request): Response
    {
        $session = $request->getSession();

        // Check if the user has completed 2FA
        $user = $this->getUser();
        $twoFactorAuth = $this->twoFactorAuthService->getOrCreateTwoFactorAuth($user);
        
        $this->logger->debug('User authenticated, checking 2FA status in panel method');
        if ($twoFactorAuth->isEnabled() && !$session->get('two_factor_auth_complete', false)) {
            $this->logger->debug('2FA is enabled for user and not completed');
            
            // Check if the user has a remember cookie
            $rememberedAuth = $this->twoFactorAuthService->checkRememberCookie($request);
            if ($rememberedAuth && $rememberedAuth->getUser()->getId() === $user->getId()) {
                // If the user has a valid remember cookie, mark as complete
                $session->set('two_factor_auth_complete', true);
                $this->logger->debug('User has valid remember cookie, marking 2FA as complete');
            } else {
                // Otherwise, redirect to verification
                $this->logger->debug('Redirecting to verification page');
                return $this->redirectToRoute('two_factor_auth_verify');
            }
        }

        $language = $request->getLocale();

        $this->getInstanceBdd();
        $solution = $this->entityManager->getRepository(Solution::class)
            ->solutionActive();
        $lstArray = [];
        if ($solution) {
            foreach ($solution as $s) {
                $lstArray[] = $s->getName();
            }
        }

        /** @var User $user */
        $user = $this->getUser();

        $countNbDocuments = $this->documentRepository->countNbDocuments();

        return $this->render('Home/index.html.twig', [
            'errorByRule' => $this->ruleRepository->errorByRule($user),
            'solutions' => $lstArray,
            'locale' => $language,
            'countNbDocuments' => $countNbDocuments,
        ]
        );
    }
}