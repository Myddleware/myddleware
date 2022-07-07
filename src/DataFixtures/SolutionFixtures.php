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
    private $manager;

    protected $solutionData = [
        ['name' => 'Airtable',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'CirrusShield',		'active' => false, 'source' => true, 'target' => true],
        ['name' => 'ERPNext',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'Facebook',			'active' => false, 'source' => true, 'target' => false],
        ['name' => 'File',				'active' => false, 'source' => true, 'target' => false],
        ['name' => 'Hubspot',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'Magento',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'Mailchimp',			'active' => false, 'source' => false, 'target' => true],
        ['name' => 'Mautic',			'active' => false, 'source' => false, 'target' => true],
        ['name' => 'MicrosoftSQL',		'active' => true, 'source' => true, 'target' => true],
        ['name' => 'Moodle',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'MySQL',				'active' => true, 'source' => true, 'target' => true],
        ['name' => 'OracleDatabase',	'active' => true, 'source' => true, 'target' => true],
        ['name' => 'PostgreSQL',		'active' => true, 'source' => true, 'target' => true],
        ['name' => 'PrestaShop',		'active' => true, 'source' => true, 'target' => true],
        ['name' => 'RingCentral',		'active' => false, 'source' => true, 'target' => false],
        ['name' => 'SageCRM',			'active' => false, 'source' => true, 'target' => true],
        ['name' => 'Salesforce',		'active' => true, 'source' => true, 'target' => true],
        ['name' => 'SAPCRM',			'active' => false, 'source' => true, 'target' => true],
        ['name' => 'SAPECC',			'active' => false, 'source' => true, 'target' => false],
        ['name' => 'Sendinblue',	    'active' => true, 'source' => true, 'target' => true],
        ['name' => 'SugarCRM',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'SuiteCRM',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'VtigerCRM',			'active' => true, 'source' => true, 'target' => true],
        ['name' => 'WooCommerce',		'active' => true, 'source' => true, 'target' => true],
        ['name' => 'WooEventManager',	'active' => false, 'source' => true, 'target' => true],
        ['name' => 'WordPress',		    'active' => false, 'source' => true, 'target' => true],
        ['name' => 'Zuora',			    'active' => true, 'source' => true, 'target' => true],
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
        }
    }

    public static function getGroups(): array
    {
        return ['mydconfig'];
    }
}
