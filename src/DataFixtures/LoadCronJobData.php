<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2022  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shapecode\Bundle\CronBundle\Entity\CronJob;

class LoadCronJobData implements FixtureInterface
{
    private $manager;
    protected array $cronJobData = [
        ['period' => '*/5 * * * *', 'command' => 'myddleware:synchro ALL', 'enable' => 1, 'description' => 'Run every active rules'],
        ['period' => '0 * * * *', 'command' => 'myddleware:rerunerror 100 5', 'enable' => 1, 'description' => 'Reload error : 1st level'],
        ['period' => '0 0 * * *', 'command' => 'myddleware:rerunerror 100 10', 'enable' => 1, 'description' => 'Reload error : 2nd level'],
        ['period' => '0 * * * *', 'command' => 'myddleware:notification alert', 'enable' => 0, 'description' => 'Alert when a task is blocked'],
        ['period' => '0 0 * * *', 'command' => 'myddleware:notification ALL', 'enable' => 0, 'description' => 'Send notification every day'],
        ['period' => '0 0 * * *', 'command' => 'myddleware:cleardata', 'enable' => 0, 'description' => 'Clean data'],
        ['period' => '0 0 1 * *', 'command' => 'myddleware:prunedatabase', 'enable' => 0, 'description' => 'Delete all rules and document with the flag deleted = 1'],
        ['period' => '0 0 * * *', 'command' => 'myddleware:checkjob', 'enable' => 1, 'description' => 'Check if a job is not closed after 15 minutes of inactivity.'],
    ];

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->generateEntities();
        $this->manager->flush();
    }

    private function generateEntities()
    {
        $cronJobs = $this->manager->getRepository(CronJob::class)->findAll();
		// Do not change cron jobs if at least one already exists
		if (empty($cronJobs)) {
			foreach ($this->cronJobData as $cronJobData) {
				$crontab = CronJob::create($cronJobData['command'], $cronJobData['period']);
				$crontab->setEnable($cronJobData['enable']);
				$crontab->setDescription($cronJobData['description']);
				$this->manager->persist($crontab);
			}
		}
    }
}
