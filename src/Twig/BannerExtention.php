<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace App\Twig;

use App\Entity\Config;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\ToolsManager;

class BannerExtention extends AbstractExtension
{

    private $em;
    private $toolsManager;

    public function __construct(EntityManagerInterface $em, ToolsManager $toolsManager)
    {
        $this->em = $em;
        $this->toolsManager = $toolsManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('entityBanner', [$this, 'getEntityBanner']),
            new TwigFunction('isPremium', [$this, 'getIsPremium']),
        ];
    }

    public function getEntityBanner()
    {
        return $this->em->getRepository(Config::class)->findAll();
    }

    // Méthode pour vérifier si l'utilisateur est premium
    public function getIsPremium()
    {
        return $this->toolsManager->isPremium();
    }
}

