<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace App\Service;

use App\Entity\Rule;
use App\Entity\Workflow;
use App\Entity\WorkflowAction;
use Doctrine\ORM\EntityManagerInterface;

class RuleCleanupService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Remove the given rule from its rule group (if any).
     * This keeps the relation consistent after deletion.
     */
    public function removeThisRuleItsRuleGroup(Rule $rule): void
    {
        $ruleGroup = $rule->getGroup();
        if ($ruleGroup) {
            $ruleGroup->removeRule($rule);
            $this->entityManager->persist($ruleGroup);
        }
    }

    /**
     * Disable and soft-delete all workflows linked to the given rule.
     * (Nom conservé "deleteWorflowsFromThisRule" pour compatibilité)
     */
    public function deleteWorflowsFromThisRule(int|string $ruleId): void
    {
        $workflows = $this->entityManager
            ->getRepository(Workflow::class)
            ->findBy(['rule' => $ruleId]);

        foreach ($workflows as $workflow) {
            $workflow->setActive(0);
            $this->deleteWorkflowActionsFromThisWorkflow((int) $workflow->getId());
            $workflow->setDeleted(1);
            $this->entityManager->persist($workflow);
        }
    }

    /**
     * Disable and soft-delete all actions from a given workflow.
     */
    public function deleteWorkflowActionsFromThisWorkflow(int|string $workflowId): void
    {
        $workflowActions = $this->entityManager
            ->getRepository(WorkflowAction::class)
            ->findBy(['workflow' => $workflowId]);

        foreach ($workflowActions as $workflowAction) {
            $workflowAction->setActive(0);
            $workflowAction->setDeleted(1);
            $this->entityManager->persist($workflowAction);
        }
    }
}
