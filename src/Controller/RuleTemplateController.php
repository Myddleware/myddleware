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

namespace App\Controller;

use Doctrine\DBAL\Connection;
use App\Manager\TemplateManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    /**
     * @Route("/rule")
     */
    class RuleTemplateController extends AbstractController
    {
        private TranslatorInterface $translator;
        protected Connection $connection;
        protected $simulationQueryField;

        public function __construct(
            Connection $connection,
            TranslatorInterface $translator,
        ) {
            $this->connection = $connection;
            $this->translator = $translator;
        }

        protected function getInstanceBdd()
        {
        }

   
    // /**
    //  * LISTE DES TEMPLATES.
    //  */
    #[Route('/list/template', name: 'regle_template', methods: ['GET'])]
    public function listTemplateAction(Request $request, TemplateManager $templateManager): Response{
        $srcSolution = (string) $request->query->get('src_solution', '');
        $tgtSolution = (string) $request->query->get('tgt_solution', '');

        if ($srcSolution === '' || $tgtSolution === '') {
            return new Response('<p class="text-muted mb-0">Select a source and target solution to see the available templates.</p>');
        }

        $templates = $templateManager->getTemplates($srcSolution, $tgtSolution);

        if (empty($templates)) {
            return new Response('<p class="text-muted mb-0">No templates available for this combination</p>');
        }

        return $this->render('Rule/create/ajax_step1/_templates.html.twig', [
            'templates'   => $templates,
            'srcSolution' => $srcSolution,
            'tgtSolution' => $tgtSolution,
        ]);
    }

    #[Route('/template/apply', name: 'regle_template_apply', methods: ['POST'])]
    public function applyTemplate(Request $request, TemplateManager $templateManager): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $ruleName          = isset($data['ruleName']) ? (string) $data['ruleName'] : '';
        $templateName      = isset($data['templateName']) ? (string) $data['templateName'] : '';
        $connectorSourceId = isset($data['connectorSourceId']) ? (int) $data['connectorSourceId'] : 0;
        $connectorTargetId = isset($data['connectorTargetId']) ? (int) $data['connectorTargetId'] : 0;
        $user = $this->getUser();
        try {
            $result = $templateManager->convertTemplate(
                $ruleName,
                $templateName,
                $connectorSourceId,
                $connectorTargetId,
                $user
            );

            if (empty($result['success'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result['message'] ?? 'Error'
                ], 500);
            }
            
            $this->addFlash('rule.template.success', $this->translator->trans('animate.template.success'));
            $redirectUrl = $this->generateUrl('regle_list');

            return new JsonResponse([
                'success'  => true,
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}