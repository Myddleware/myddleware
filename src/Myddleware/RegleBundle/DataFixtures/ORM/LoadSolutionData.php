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

namespace Myddleware\RegleBundle\DataFixtures\ORM;
 
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Myddleware\RegleBundle\Entity\Solution;
 
class LoadSolutionData implements FixtureInterface
{
    private $manager; 
	protected $solutionData = array(
									array('name' => 'sugarcrm',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'vtigercrm',		'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'salesforce',		'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'prestashop',		'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'dolist',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'suitecrm',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'mailchimp',		'active' => 1,'source' => 0,'target' => 1),
									array('name' => 'bittle',			'active' => 0,'source' => 0,'target' => 1),
									array('name' => 'sagecrm',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'sapcrm',			'active' => 0,'source' => 1,'target' => 1),
									array('name' => 'mysql',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'oracledb',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'sapecc',			'active' => 0,'source' => 1,'target' => 0),
									array('name' => 'magento',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'moodle',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'file',				'active' => 1,'source' => 1,'target' => 0),
									array('name' => 'shopapplication',	'active' => 0,'source' => 1,'target' => 1),
									array('name' => 'sagelive',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'microsoftsql',		'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'ringcentral',		'active' => 1,'source' => 1,'target' => 0),
									array('name' => 'cirrusshield',		'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'zuora',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'sage50',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'hubspot',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'erpnext',			'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'myddlewareapi',	'active' => 1,'source' => 1,'target' => 0),
									array('name' => 'medialogistique',	'active' => 1,'source' => 1,'target' => 1),
									array('name' => 'mautic',			'active' => 1,'source' => 0,'target' => 1),
							);
 
    public function load(ObjectManager $manager){
        $this->manager = $manager; 
        $this->generateEntities(); 
        $this->manager->flush();
    }

    private function generateEntities() {
        foreach($this->solutionData as $solution) {
			// Check if the solution doesn't exist in Myddleware we create it else we update it
			$sol = $this->manager
						 ->getRepository('RegleBundle:Solution')
						 ->findOneByName($solution['name']);
			if (
					empty($sol)
				 || empty($sol->getId()	)
			) {	
				$sol = new Solution();
			}
			$sol->setName($solution['name']);
			$sol->setActive($solution['active']);
			$sol->setSource($solution['source']);
			$sol->setTarget($solution['target']);
			$this->manager->persist($sol); 
        }
    }
 
}