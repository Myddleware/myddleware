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

class RuleDuplicateService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function duplicateWorkflows($id, Rule $newRule)
    {
        // start by getting the rule fromthe id
        $rule = $this->entityManager
            ->getRepository(Rule::class)
            ->findOneBy([
                'id' => $id,
            ]);

        // then get all the workflows linked to this rule
        $workflows = $rule->getWorkflows();

        // then duplicate each workflow, create a new one with the same name and link it to the new rule
        foreach ($workflows as $workflow) {
            $newWorkflow = new Workflow();
            $newWorkflow->setId(uniqid());
            $ruleName = substr($newRule->getName(), 0, 5);
            $workflowName = $workflow->getName();
            $newWorkflow->setName($workflowName. "-duplicate-".$ruleName);
            $newWorkflow->setRule($newRule);
            $newWorkflow->setDeleted(false);
            $newWorkflow->setCreatedBy($this->getUser());
            $newWorkflow->setModifiedBy($this->getUser());
            $newWorkflow->setDateCreated(new \DateTime());
            $newWorkflow->setDateModified(new \DateTime());
            $newWorkflow->setCondition($workflow->getCondition());
            $newWorkflow->setDescription($workflow->getDescription());
            $newWorkflow->setActive($workflow->getActive());
            $newWorkflow->setOrder($workflow->getOrder());
            $this->entityManager->persist($newWorkflow);

            $this->entityManager->flush();

            $this->duplicateWorkflowActions($workflow, $newWorkflow);
        }
    }

    public function duplicateWorkflowActions(Workflow $workflow, Workflow $newWorkflow): void
    {
        // duplicate the actions of the workflow
        $actions = $workflow->getWorkflowActions();
        foreach ($actions as $action) {
            $newAction = new WorkflowAction();
            $newAction->setId(uniqid());
            $newAction->setWorkflow($newWorkflow);
            $newAction->setCreatedBy($this->getUser());
            $newAction->setModifiedBy($this->getUser());
            $newAction->setDateCreated(new \DateTime());
            $newAction->setDateModified(new \DateTime());
            $newAction->setName($action->getName());
            $newAction->setAction($action->getAction());
            $newAction->setDescription($action->getDescription());
            $newAction->setOrder($action->getOrder());
            $newAction->setArguments($action->getArguments());
            $newAction->setDeleted(false);
            $newAction->setActive($action->getActive());
            $this->entityManager->persist($newAction);
        }

        $this->entityManager->flush();
    }
}
