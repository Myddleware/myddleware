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

namespace App\Repository;

use App\Entity\WebhookEvent;
use App\Service\DebugLogger;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WebhookEventRepository extends ServiceEntityRepository
{
    // Format used for the column received_at and for the reference date of the webhook rules
    public const DATE_FORMAT = 'Y-m-d H:i:s.u';

    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, WebhookEvent::class);
        $this->debugLogger = $debugLogger;
    }

    /**
     * Save an event. We use DBAL instead of the ORM to keep the microseconds of received_at.
     * Return false if the event has already been received (same external id for the same connector).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertEvent(int $connId, array $event, DateTime $receivedAt): bool
    {
        try {
            $this->getEntityManager()->getConnection()->insert('webhookevent', [
                'conn_id' => $connId,
                'module' => $event['module'],
                'event_type' => $event['event_type'] ?? null,
                'record_id' => (string) $event['record_id'],
                'external_id' => $event['external_id'] ?? null,
                'deletion' => empty($event['deletion']) ? 0 : 1,
                'payload' => isset($event['payload']) ? json_encode($event['payload']) : null,
                'received_at' => $receivedAt->format(self::DATE_FORMAT),
            ]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the records to read for a rule : one row per record id with the date of its last event,
     * sorted by this date. Only events received before $maxDate are returned, so we don't skip
     * an event being saved while the rule is reading.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function findRecordsToRead(int $connId, string $module, string $dateRef, DateTime $maxDate, int $limit): array
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['connId' => $connId, 'module' => $module, 'dateRef' => $dateRef, 'limit' => $limit]);
        $__debugReturn = null;
        try {
            $sql = 'SELECT record_id, MAX(received_at) received_at
                    FROM webhookevent
                    WHERE conn_id = :connId
                        AND module = :module
                        AND deletion = 0
                        AND received_at > :dateRef
                        AND received_at < :maxDate
                    GROUP BY record_id
                    ORDER BY received_at ASC
                    LIMIT '.(int) $limit;
            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->bindValue('connId', $connId);
            $stmt->bindValue('module', $module);
            $stmt->bindValue('dateRef', $dateRef);
            $stmt->bindValue('maxDate', $maxDate->format(self::DATE_FORMAT));

            return $__debugReturn = $stmt->executeQuery()->fetchAllAssociative();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    /**
     * Remove the events received before the limit date.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteReceivedBefore(DateTime $limitDate): int
    {
        return $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM webhookevent WHERE received_at < :limitDate',
            ['limitDate' => $limitDate->format(self::DATE_FORMAT)]
        );
    }
}
