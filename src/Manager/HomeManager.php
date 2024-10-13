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

namespace App\Manager;

use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Repository\JobRepository;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class HomeManager
{
    protected Connection $connection;
    protected LoggerInterface $logger;

    const historicDays = 7;
    const nbHistoricJobs = 5;
    protected string $historicDateFormat = 'M-d';
    private JobRepository $jobRepository;
    private DocumentRepository $documentRepository;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        JobRepository $jobRepository,
        DocumentRepository $documentRepository
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->jobRepository = $jobRepository;
        $this->documentRepository = $documentRepository;
    }

    // public function countTransferHisto(User $user = null): array
    // {
    //     try {
    //         $historic = [];
    //         // Start date
    //         $startDate = date('Y-m-d', strtotime('-'.self::historicDays.' days'));
    //         // End date
    //         $endDate = date('Y-m-d');
    //         // Init array
    //         while (strtotime($startDate) < strtotime($endDate)) {
    //             $startDateFormat = date($this->historicDateFormat, strtotime('+1 day', strtotime($startDate)));
    //             $startDate = date('Y-m-d', strtotime('+1 day', strtotime($startDate)));
    //             $historic[$startDate] = ['date' => $startDateFormat, 'open' => 0, 'error' => 0, 'cancel' => 0, 'close' => 0];
    //         }

    //         // Select the number of transfers per day
    //         $result = $this->documentRepository->countTransferHisto($user);
    //         if (!empty($result)) {
    //             foreach ($result as $row) {
    //                 $historic[$row['date']][strtolower($row['globalStatus'])] = $row['nb'];
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
    //     }

    //     return $historic;
    // }
}
