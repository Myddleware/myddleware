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
use Myddleware\RegleBundle\Entity\JobScheduler;
 
class LoadJobSchedulerData implements FixtureInterface
{
    private $manager; 
	protected $jobSchedulerData = array(
									array('command' => 'synchro', 	'param1' => 'ALL','param2' => '', 	'period' => 5), 
									array('command' => 'rerunError','param1' => '100','param2' => '5',	'period' => 60), 
									array('command' => 'rerunError','param1' => '100','param2' => '10',	'period' => 1440), 
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
        foreach($this->jobSchedulerData as $jobScheduler) {
            $this->newEntity($jobScheduler);
        }
    }
 
    private function newEntity($jobScheduler) {
	
		// Add jobs only if the table is empty
		$jobSchedulers = $this->manager
								 ->getRepository('RegleBundle:JobScheduler')
								 ->findAll();
		if (
				empty($jobSchedulers)
		) {	
			$jobSchedulerObject = new JobScheduler();
			$jobSchedulerObject->setDateCreated(new \DateTime('now'));
			$jobSchedulerObject->setDateModified(new \DateTime('now'));
			$jobSchedulerObject->setCreatedBy('1');
			$jobSchedulerObject->setModifiedBy('1');
			$jobSchedulerObject->setCommand($jobScheduler['command']);
			$jobSchedulerObject->setParam1($jobScheduler['param1']);
			$jobSchedulerObject->setParam2($jobScheduler['param2']);
			$jobSchedulerObject->setPeriod($jobScheduler['period']);
			$jobSchedulerObject->setLastRun(new \DateTime('now'));
			$this->manager->persist($jobSchedulerObject);
		}	
    }
}