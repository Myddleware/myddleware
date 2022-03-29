<?php

declare(strict_types=1);
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

namespace App\DataFixtures;

use App\Entity\Solution;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SolutionFixtures extends Fixture implements FixtureGroupInterface
{
    public const DEFAULT_SOLUTIONS_REFERENCE = 'default-solutions';

    private $manager;
    protected $solutionData = [
        ['name' => 'Airtable',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'CirrusShield',		'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'ERPNext',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'Facebook',			'active' => 0, 'source' => 1, 'target' => 0],
        ['name' => 'File',				'active' => 0, 'source' => 1, 'target' => 0],
        ['name' => 'Hubspot',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'Magento',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'Mailchimp',			'active' => 0, 'source' => 0, 'target' => 1],
        ['name' => 'Mautic',			'active' => 0, 'source' => 0, 'target' => 1],
        ['name' => 'MicrosoftSQL',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'Moodle',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'MySQL',				'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'OracleDatabase',	'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'PostgreSQL',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'PrestaShop',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'RingCentral',		'active' => 0, 'source' => 1, 'target' => 0],
        ['name' => 'SageCRM',			'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'Salesforce',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'SAPCRM',			'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'SAPECC',			'active' => 0, 'source' => 1, 'target' => 0],
        ['name' => 'Sendinblue',	    'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'SugarCRM',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'SuiteCRM',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'VtigerCRM',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'WooCommerce',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'WooEventManager',	'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'WordPress',		    'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'Zuora',			    'active' => 1, 'source' => 1, 'target' => 1],
    ];

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->generateEntities();
        $this->manager->flush();
    }

    private function generateEntities()
    {
        // Get all solutions already in the database
        $solutions = $this->manager->getRepository(Solution::class)->findAll();
        foreach ($this->solutionData as $solutionData) {
            $foundSolution = false;
            if (!empty($solutions)) {
                foreach ($solutions as $solution) {
                    if ($solution->getName() == $solutionData['name']) {
                        $foundSolution = true;
                        $sol = $solution;
                        break;
                    }
                }
            }

            // If we didn't found the solution we create a new one, otherwise we update it
            if (!$foundSolution) {
                $sol = new Solution();
            }
            $sol->setName($solutionData['name']);
            $sol->setActive($solutionData['active']);
            $sol->setSource($solutionData['source']);
            $sol->setTarget($solutionData['target']);
            $this->manager->persist($sol);
            $this->addReference(self::DEFAULT_SOLUTIONS_REFERENCE, $sol->getName());
        }
    }

    public static function getGroups(): array
    {
        return ['mydconfig'];
    }
}
