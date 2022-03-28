<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  St�phane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  St�phane Faure - Myddleware ltd - contact@myddleware.com
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

use DateTimeImmutable;
use App\Entity\JobScheduler;
use App\DataFixtures\UserFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class JobSchedulerFixtures extends Fixture implements DependentFixtureInterface
{
    private $manager;
    protected $jobSchedulerData = [
        ['command' => 'synchro', 		'paramName1' => 'rule', 'paramValue1' => 'ALL', 	'paramName2' => '',			'paramValue2' => '', 	'period' => 5,		'jobOrder' => 10,	'active' => 1],
        ['command' => 'rerunerror',	'paramName1' => 'limit', 'paramValue1' => '100', 	'paramName2' => 'attempt',	'paramValue2' => '5',	'period' => 60, 	'jobOrder' => 100,	'active' => 1],
        ['command' => 'rerunerror',	'paramName1' => 'limit', 'paramValue1' => '100', 	'paramName2' => 'attempt',	'paramValue2' => '10',	'period' => 1440,	'jobOrder' => 110,	'active' => 1],
        ['command' => 'notification',	'paramName1' => 'type',	'paramValue1' => 'alert',	'paramName2' => '',			'paramValue2' => '', 	'period' => 60,		'jobOrder' => 200,	'active' => 1],
        ['command' => 'notification',	'paramName1' => '',		'paramValue1' => '',		'paramName2' => '',			'paramValue2' => '', 	'period' => 1440,	'jobOrder' => 210,	'active' => 1],
        ['command' => 'cleardata',		'paramName1' => '',		'paramValue1' => '',		'paramName2' => '',			'paramValue2' => '', 	'period' => 1440,	'jobOrder' => 300,	'active' => 1],
    ];

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->generateEntities();
        $this->manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }

    private function generateEntities()
    {
        foreach ($this->jobSchedulerData as $jobScheduler) {
            $this->newEntity($jobScheduler);
        }
    }

    private function newEntity($jobScheduler)
    {
        // Add jobs only if the table is empty
        $jobSchedulers = $this->manager->getRepository(JobScheduler::class)->findAll();
        if (empty($jobSchedulers)) {
            $jobSchedulerObject = new JobScheduler();
            $jobSchedulerObject->setCreatedAt(new DateTimeImmutable('now'));
            $jobSchedulerObject->setUpdatedAt(new DateTimeImmutable('now'));
            // TODO: change this->getUser to reflect calling 1st a User Fixture which will then be used to create this rel
            // or alternatively we could set this property to nullable?
            $jobSchedulerObject->setCreatedBy($this->getUser());
            $jobSchedulerObject->setModifiedBy($this->getUser());
            $jobSchedulerObject->setCommand($jobScheduler['command']);
            $jobSchedulerObject->setParamName1($jobScheduler['paramName1']);
            $jobSchedulerObject->setParamValue1($jobScheduler['paramValue1']);
            $jobSchedulerObject->setParamName2($jobScheduler['paramName2']);
            $jobSchedulerObject->setParamValue2($jobScheduler['paramValue2']);
            $jobSchedulerObject->setPeriod($jobScheduler['period']);
            $jobSchedulerObject->setActive($jobScheduler['active']);
            $jobSchedulerObject->setJobOrder($jobScheduler['jobOrder']);
            $this->manager->persist($jobSchedulerObject);
        }
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }
}
