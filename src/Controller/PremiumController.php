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

use Exception;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/premium")
 */
class PremiumController extends AbstractController
{


    protected Connection $connection;
    // To allow sending a specific record ID to rule simulation
    protected $simulationQueryField;

    public function __construct(
    ) {
    }

    protected function getInstanceBdd() {}



/**
     * PAGE ACHAT PREMIUM.
     *
     * @return RedirectResponse|Response
     */
    #[Route('/list', name: 'premium_list', defaults: ['page' => 1])]
    public function PremiumListAction(Request $request, int $page = 1)
    {
        try {
            // Si ce n'est pas une requête AJAX, rendre la page complète
            return $this->render(
                'Premium/list.html.twig',
                [
                    'isPremium' => true,
                ]
            );
        } catch (Exception $e) {
            throw $this->createNotFoundException('Erreur : ' . $e->getMessage());
        }
    }




}
