<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace App\Manager;

use App\Repository\RuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class JobSchedulerManager
{
    protected $env;
    protected EntityManagerInterface $entityManager;
    protected array $jobList = ['cleardata', 'notification', 'rerunerror', 'synchro'];
    private LoggerInterface $logger;
    private RuleRepository $ruleRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RuleRepository $ruleRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function getJobsParams(): array
    {
        try {
            $list = [];
            if (!empty($this->jobList)) {
                foreach ($this->jobList as $job) {
                    $list[$job]['name'] = $job;
                    switch ($job) {
                        case 'synchro':
                            $list[$job]['param1'] = [
                                'rule' => [
                                    'fieldType' => 'list',
                                    'option' => $this->getAllActiveRules(),
                                ],
                            ];
                            break;
                        case 'notification':
                            $list[$job]['param1'] = [
                                'type' => [
                                    'fieldType' => 'list',
                                    'option' => ['alert' => 'alert', 'statistics' => 'statistics'],
                                ],
                            ];
                            break;
                        case 'rerunerror':
                            $list[$job]['param1'] = [
                                'limit' => [
                                    'fieldType' => 'int',
                                ],
                            ];
                            $list[$job]['param2'] = [
                                'attempt' => [
                                    'fieldType' => 'int',
                                ],
                            ];
                            break;
                    }
                }
            }

            return $list;
        } catch (Exception $e) {
            throw new Exception('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
    }

    private function getAllActiveRules(): array
    {
        $rules['ALL'] = 'All active rules';
        $activeRules = $this->ruleRepository->findActiveRules();
        if (!empty($activeRules)) {
            foreach ($activeRules as $activeRule) {
                $rules[$activeRule['id']] = $activeRule['name'];
            }
        }

        return $rules;
    }
}
