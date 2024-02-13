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

namespace App\DataFixtures;

use App\Entity\Solution;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadSolutionData implements FixtureInterface
{
    private $manager;
    protected $solutionData = [
        ['name' => 'sugarcrm',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'vtigercrm',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'salesforce',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'prestashop',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'suitecrm',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'mailchimp',			'active' => 0, 'source' => 0, 'target' => 1],
        ['name' => 'sagecrm',			'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'sapcrm',			'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'mysql',				'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'oracledb',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'sapecc',			'active' => 0, 'source' => 1, 'target' => 0],
        ['name' => 'magento',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'moodle',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'file',				'active' => 1, 'source' => 1, 'target' => 0],
        ['name' => 'microsoftsql',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'ringcentral',		'active' => 0, 'source' => 1, 'target' => 0],
        ['name' => 'cirrusshield',		'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'zuora',			    'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'hubspot',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'erpnext',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'mautic',			'active' => 0, 'source' => 0, 'target' => 1],
        ['name' => 'facebook',			'active' => 0, 'source' => 1, 'target' => 0],
        ['name' => 'woocommerce',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'wooeventmanager',	'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'wordpress',		    'active' => 0, 'source' => 1, 'target' => 1],
        ['name' => 'postgresql',		'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'airtable',			'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'sendinblue',	    'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'yousign',           'active' => 1, 'source' => 1, 'target' => 1],
        ['name' => 'suitecrm8',         'active' => 1, 'source' => 1, 'target' => 1],
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
}
