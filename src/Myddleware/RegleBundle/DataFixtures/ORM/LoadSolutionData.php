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
									array('id' => '1', 'name' => 'sugarcrm',			'active' => 1,'source' => 1,'target' => 1),
									array('id' => '2', 'name' => 'salesforce',			'active' => 1,'source' => 1,'target' => 1),
									array('id' => '3', 'name' => 'prestashop',			'active' => 1,'source' => 1,'target' => 1),
									array('id' => '4', 'name' => 'dolist',				'active' => 1,'source' => 1,'target' => 1),
									array('id' => '5', 'name' => 'eventbrite',			'active' => 1,'source' => 1,'target' => 0),
									array('id' => '6', 'name' => 'suitecrm',			'active' => 1,'source' => 1,'target' => 1),
									array('id' => '7', 'name' => 'mailchimp',			'active' => 1,'source' => 0,'target' => 1),
									array('id' => '8', 'name' => 'bittle',				'active' => 1,'source' => 0,'target' => 1),
									array('id' => '9', 'name' => 'sagecrm',				'active' => 1,'source' => 1,'target' => 1),
									array('id' => '10', 'name' => 'sapcrm',				'active' => 0,'source' => 1,'target' => 1),
									array('id' => '11', 'name' => 'mysql',				'active' => 1,'source' => 1,'target' => 1),
									array('id' => '12', 'name' => 'sapecc',				'active' => 0,'source' => 1,'target' => 0),
									array('id' => '13', 'name' => 'magento',			'active' => 1,'source' => 1,'target' => 1),
									array('id' => '14', 'name' => 'moodle',				'active' => 1,'source' => 1,'target' => 1),
									array('id' => '15', 'name' => 'file',				'active' => 1,'source' => 1,'target' => 0),
									array('id' => '16', 'name' => 'shopapplication',	'active' => 1,'source' => 1,'target' => 1),
									array('id' => '17', 'name' => 'sagelive',			'active' => 1,'source' => 1,'target' => 1),
									array('id' => '18', 'name' => 'microsoftsql',		'active' => 1,'source' => 1,'target' => 1),
							);
 
    public function load(ObjectManager $manager){
        $this->manager = $manager; 
        $this->generateEntities(); 
        $this->manager->flush();
    }
 
    public function getOrder() {
        return 1; 
    }
 
    private function generateEntities() {
        foreach($this->solutionData as $solution) {
			$sol = new Solution();
			$sol->setName($solution['name']);
			$sol->setActive($solution['active']);
			$sol->setSource($solution['source']);
			$sol->setTarget($solution['target']);
			$this->manager->persist($sol);
        }
    }
 
}