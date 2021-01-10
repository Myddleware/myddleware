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

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Entity\Document;
use App\Entity\FuncCat;
use App\Entity\Functions;
use App\Entity\Rule;
use App\Entity\RuleAudit;
use App\Entity\RuleField;
use App\Entity\RuleFilter;
use App\Entity\RuleParam;
use App\Entity\RuleParamAudit;
use App\Entity\RuleRelationShip;
use App\Entity\Solution;
use App\Entity\User;
use App\Form\ConnectorType;
use App\Manager\DocumentManager;
use App\Manager\FormulaManager;
use App\Manager\HomeManager;
use App\Manager\job;
use App\Manager\JobManager;
use App\Manager\rule as RuleClass;
use App\Manager\RuleManager;
use App\Manager\SolutionManager;
use App\Manager\template;
use App\Manager\TemplateManager;
use App\Manager\tools;
use App\Manager\ToolsManager;
use App\Repository\DocumentRepository;
use App\Repository\JobRepository;
use App\Repository\RuleRepository;
use App\Service\SessionService;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Encryption\Encrypter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/* * * * * * * *  * * * * * *  * * * * * *
    si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Controller/DefaultController.php';
if (file_exists($file)) {
    require_once $file;
} else {
    //Sinon on met la classe suivante
    /**
     * Class DefaultControllerCore.
     *
     * @package App\Controller
     * @Route("/rule")
     */
    class DefaultController extends AbstractController
    {
        private $formuleManager;
        private $sessionService;
        /**
         * @var mixed
         */
        private $locale;
        /**
         * @var ParameterBagInterface
         */
        private $params;
        /**
         * @var EntityManagerInterface
         */
        private $entityManager;
        /**
         * @var HomeManager
         */
        private $home;

        /**
         * @var ToolsManager
         */
        private $tools;
        /**
         * @var TranslatorInterface
         */
        private $translator;
        /**
         * @var AuthorizationCheckerInterface
         */
        private $authorizationChecker;
        /**
         * @var JobManager
         */
        private $job;
        /**
         * @var LoggerInterface
         */
        private $logger;
        /**
         * @var TemplateManager
         */
        private $template;
        /**
         * @var RuleRepository
         */
        private $ruleRepository;
        /**
         * @var JobRepository
         */
        private $jobRepository;
        /**
         * @var DocumentRepository
         */
        private $documentRepository;
        /**
         * @var SolutionManager
         */
        private $solutionManager;
        /**
         * @var RuleManager
         */
        private $ruleManager;
        /**
         * @var DocumentManager
         */
        private $documentManager;

        public function __construct(
            LoggerInterface $logger,
            RuleManager $ruleManager,
            FormulaManager $formuleManager,
            SolutionManager $solutionManager,
            DocumentManager $documentManager,
            SessionService $sessionService,
            EntityManagerInterface $entityManager,
            RuleRepository $ruleRepository,
            JobRepository $jobRepository,
            DocumentRepository $documentRepository,
            Connection $connection,
            TranslatorInterface $translator,
            AuthorizationCheckerInterface $authorizationChecker,
            HomeManager $home,
            ToolsManager $tools,
            JobManager $job,
            TemplateManager $template,
            ParameterBagInterface $params
        ) {
            $this->logger = $logger;
            $this->ruleManager = $ruleManager;
            $this->formuleManager = $formuleManager;
            $this->solutionManager = $solutionManager;
            $this->documentManager = $documentManager;
            $this->sessionService = $sessionService;
            $this->entityManager = $entityManager;
            $this->ruleRepository = $ruleRepository;
            $this->jobRepository = $jobRepository;
            $this->documentRepository = $documentRepository;
            $this->connection = $connection;
            $this->translator = $translator;
            $this->authorizationChecker = $authorizationChecker;
            $this->home = $home;
            $this->tools = $tools;
            $this->job = $job;
            $this->template = $template;
            $this->params = $params;
            $this->locale = $params->get('locale');
        }

        // Connexion direct bdd (utilisé pour créer les tables Z sans doctrine
        protected $connection;
        // Standard rule param list to avoird to delete specific rule param (eg : filename for file connector)
        protected $standardRuleParam = ['datereference', 'bidirectional', 'fieldId', 'mode', 'duplicate_fields', 'limit', 'delete', 'fieldDateRef', 'fieldId', 'targetFieldId', 'deletionField', 'deletion', 'language'];

        protected function getInstanceBdd()
        {
        }

        /* ******************************************************
         * RULE
         ****************************************************** */

        /**
         * LISTE DES REGLES.
         *
         * @return RedirectResponse|Response
         *
         * @Route("/list", name="regle_list", defaults={"page"=1})
         * @Route("/list/page-{page}", name="regle_list_page", requirements={"page"="\d+"})
         */
        public function ruleListAction(int $page = 1)
        {
            try {
                $key = $this->sessionService->getParamRuleLastKey();
                if (null != $key && $this->sessionService->isRuleIdExist($key)) {
                    $id = $this->sessionService->getRuleId($key);
                    $this->sessionService->removeRuleId($key);

                    return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
                }

                $this->getInstanceBdd();
                $compact['nb'] = 0;

                $compact = $this->nav_pagination([
                    'adapter_em_repository' => $this->entityManager->getRepository(Rule::class)->findListRuleByUser($this->getUser()),
                    'maxPerPage' => $this->params->get('pager'),
                    'page' => $page,
                ]);

                // Si tout se passe bien dans la pagination
                if ($compact) {
                    // Si aucune règle
                    if ($compact['nb'] < 1 && !intval($compact['nb'])) {
                        $compact['entities'] = '';
                        $compact['pager'] = '';
                    }

                    return $this->render('Rule/list.html.twig', [
                        'nb_rule' => $compact['nb'],
                        'entities' => $compact['entities'],
                        'pager' => $compact['pager'],
                    ]
                    );
                }
                throw $this->createNotFoundException('Error');
                // ---------------
            } catch (Exception $e) {
                throw $this->createNotFoundException('Error : '.$e);
            }
        }

        /**
         * SUPPRESSION D'UNE REGLE.
         *
         * @param $id
         *
         * @return RedirectResponse
         *
         * @Route("/delete/{id}", name="regle_delete")
         */
        public function ruleDeleteAction(Request $request, $id)
        {
            $session = $request->getSession();

            // First, checking that the rule has document not deleted
            $docClose = $this->getDoctrine()
                ->getManager()
                ->getRepository(Document::class)
                ->findOneBy([
                    'rule' => $id,
                    'deleted' => 0,
                ]
                );
            // Return to the view detail for the rule if we found a document close
            if (!empty($docClose)) {
                $session->set('error', [$this->translator->trans('error.rule.delete_document')]);

                return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
            }

            // Then, checking that the rule has no document open or in error
            $docErrorOpen = $this->getDoctrine()
                ->getManager()
                ->getRepository(Document::class)
                ->findOneBy([
                    'rule' => $id,
                    'deleted' => 0,
                    'globalStatus' => ['Open', 'Error'],
                ]
                );
            // Return to the view detail of the rule if we found a document open or in error
            if (!empty($docErrorOpen)) {
                $session->set('error', [$this->translator->trans('error.rule.delete_document_error_open')]);

                return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
            }

            // Checking if the rule is linked to an other one
            $ruleRelationships = $this->getDoctrine()
                ->getManager()
                ->getRepository(RuleRelationShip::class)
                ->findBy(['fieldId' => $id]);

            // Return to the view detail of the rule if a rule relate to this one exists and is not deleted
            if (!empty($ruleRelationships)) {
                foreach ($ruleRelationships as $ruleRelationship) {
                    if (empty($ruleRelationship->getDeleted())) {
                        $session->set('error', [$this->translator->trans('error.rule.delete_relationship_exists').$ruleRelationship->getRule()]);

                        return $this->redirect($this->generateUrl('regle_open', ['id' => $id]));
                    }
                }
            }

            if ($this->getUser()->isAdmin()) {
                $list_fields_sql =
                    ['id' => $id,
                    ];
            } else {
                $list_fields_sql =
                    [
                        'id' => $id,
                        'createdBy' => $this->getUser()->getId(),
                    ];
            }
            // Detecte si la session est le support ---------

            if (isset($id)) {
                // Récupère la règle en fonction de son id
                $rule = $this->getDoctrine()
                    ->getManager()
                    ->getRepository(Rule::class)
                    ->findBy($list_fields_sql);

                $rule = $rule[0];

                // si je supprime une règle qui ne m'appartient pas alors redirection
                if (empty($rule)) {
                    return $this->redirect($this->generateUrl('regle_list'));
                }

                // On récupére l'EntityManager
                $this->getInstanceBdd();

                // Remove the rule relationships
                $ruleRelationships = $this->getDoctrine()
                    ->getManager()
                    ->getRepository(RuleRelationShip::class)
                    ->findBy(['rule' => $id]);

                if (!empty($ruleRelationships)) {
                    foreach ($ruleRelationships as $ruleRelationship) {
                        $ruleRelationship->setDeleted(1);
                        $this->entityManager->persist($ruleRelationship);
                    }
                }

                $rule->setDeleted(1);
                $rule->setActive(0);
                $this->entityManager->persist($rule);
                $this->entityManager->flush();

                return $this->redirect($this->generateUrl('regle_list'));
            }
        }

        // AFFICHE LES FLUX D'UNE REGLE

        /**
         * @param $id
         *
         * @return RedirectResponse
         *
         * @Route("/displayflux/{id}", name="regle_displayflux")
         */
        public function displayFluxAction($id)
        {
            $rule = $this->getDoctrine()
                ->getManager()
                ->getRepository(Rule::class)
                ->findOneBy([
                    'id' => $id,
                ]
                );

            $this->sessionService->setFluxFilterWhere("WHERE Document.deleted = 0 AND Document.rule_id = '".$rule->getId()."'");
            $this->sessionService->setFluxFilterRuleName($rule->getName());

            return $this->redirect($this->generateUrl('flux_list', ['search' => 1]));
        }

        /**
         * ACTIVE UNE REGLE.
         *
         * @param $id
         *
         * @return JsonResponse|Response
         *
         * @Route("/update/{id}", name="regle_update")
         */
        public function ruleUpdActiveAction($id)
        {
            try {
                // On récupére l'EntityManager
                $this->getInstanceBdd();

                $rule = $this->getDoctrine()
                    ->getManager()
                    ->getRepository(Rule::class)
                    ->findOneById($id);

                if ($rule->getActive()) {
                    $r = 0;
                    $rule->setActive($r);
                } else {
                    $r = 1;
                    $rule->setActive($r);
                }

                $this->entityManager->persist($rule);
                $this->entityManager->flush();

                return new Response($r);
            } catch (Exception $e) {
                return new JsonResponse($e->getMessage());
            }
        }

        /**
         * Executer une règle manuellement.
         *
         * @param $id
         *
         * @return RedirectResponse
         *
         * @Route("/exec/{id}", name="regle_exec")
         */
        public function ruleExecAction($id)
        {
            try {
                if ('ALL' == $id) {
                    $this->ruleManager->actionRule('ALL');

                    return $this->redirect($this->generateUrl('regle_list'));
                } elseif ('ERROR' == $id) {
                    $this->ruleManager->actionRule('ERROR');

                    return $this->redirect($this->generateUrl('regle_list'));
                }
                $rule = $this->ruleRepository->find($id);
                $this->ruleManager->actionRule('runMyddlewareJob', $rule);

                return $this->redirect($this->generateURL('regle_open', ['id' => $id]));
            } catch (Exception $e) {
                return $this->redirect($this->generateUrl('regle_list'));
            }
        }

        /**
         * MODIFIE LES PARAMETRES D'UNE REGLE.
         *
         * @param $id
         *
         * @return JsonResponse|Response
         *
         * @Route("/update/params/{id}", name="path_fiche_params_update")
         */
        public function ruleUpdParamsAction($id)
        {
            try {
                // On récupére l'EntityManager
                $this->getInstanceBdd();
                if (isset($_POST['params']) && is_array($_POST['params'])) {
                    foreach ($_POST['params'] as $p) {
                        $param = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'rule' => $id,
                                'name' => $p['name'],
                            ]
                            );
                        // In a few case, the parameter could not exist, in this case we create it
                        if (empty($param)) {
                            $param = new RuleParam();
                            $param->setRule($id);
                            $param->setName($p['name']);
                            $param->setValue($p['value']);
                        } else {
                            // Save param modification in the audit table
                            if ($p['value'] != $param->getValue()) {
                                $paramAudit = new RuleParamAudit();
                                $paramAudit->setRuleParamId($p['id']);
                                $paramAudit->setDateModified(new \DateTime());
                                $paramAudit->setBefore($param->getValue());
                                $paramAudit->setAfter($p['value']);
                                $paramAudit->setByUser($this->getUser()->getId());
                                $this->entityManager->persist($paramAudit);
                            }
                            $param->setValue($p['value']);
                        }
                        $this->entityManager->persist($param);
                        $this->entityManager->flush();
                    }
                }

                return new Response(1);
            } catch (Exception $e) {
                return new JsonResponse($e->getMessage());
            }
        }

        /**
         * SIMULE LA LECTURE POUR RETOURNER LE NOMBRE DE TRANSFERS POTENTIELS.
         *
         * @param $id
         *
         * @return Response
         *
         * @Route("/simule/{id}", name="path_fiche_params_simulate")
         */
        public function ruleSimulateTransfersAction(Rule $rule)
        {
            try {
                // On récupére l'EntityManager
                $this->getInstanceBdd();

                // Get the rule reference
                $param['date_ref'] = $rule->getParamByName('datereference')->getValue();

                // Get the rule limit
                $limitParam = $rule->getParamByName('limit');
                if ($limitParam) {
                    $param['limit'] = $limitParam->getValue();
                }

                // Get the other rule params
                $connectorParams = $rule->getParams();
                foreach ($connectorParams as $connectorParam) {
                    $param['ruleParams'][$connectorParam->getName()] = $connectorParam->getValue();
                }

                $param['fields'] = [];
                // Extraction des champs sources
                foreach ($rule->getFields() as $ruleField) {
                    // It could be several fields in a source when there is a formula
                    $sources = explode(';', $ruleField->getSource());
                    foreach ($sources as $source) {
                        $param['fields'][] = $source;
                    }
                }

                // Module source
                $param['module'] = (string) $rule->getModuleSource();

                // Solution source
                $solution_source_nom = $rule->getConnectorSource()->getSolution()->getName();

                // Connector source -------------------
                $connectorParamsSource = $rule->getConnectorSource();
                $connectorSource['solution'] = $rule->getConnectorSource()->getSolution()->getName();

                foreach ($connectorParamsSource as $connector) {
                    $connectorSource[$connector->getName()] = $connector->getValue();
                }

                $solution_source = $this->solutionManager->get($solution_source_nom);
                $solution_source->login($connectorSource);

                // Rule Mode
                $param['rule']['mode'] = $rule->getParamByName('mode')->getValue();

                if (empty($param['rule']['mode'])) {
                    $param['rule']['mode'] = '0';
                }
                $param['offset'] = '0';
                $result = $solution_source->read($param);

                if (!empty($result['error'])) {
                    throw new Exception('Reading Issue: '.$result['error']);
                }
                if (isset($result['count'])) {
                    return new Response($result['count']);
                }

                return new Response(0);
            } catch (Exception $e) {
                return new Response(json_encode(['error' => $e->getMessage()]));
            }
        }

        /**
         * MODE EDITION D'UNE REGLE.
         *
         * @return RedirectResponse
         *
         * @Route("/edit/{id}", name="regle_edit")
         */
        public function ruleEditAction(Request $request, Rule $rule)
        {
            $session = $request->getSession();

            try {
                // First, checking that the rule has no document open or in error
                $docErrorOpen = $this->getDoctrine()
                    ->getManager()
                    ->getRepository(Document::class)
                    ->findOneBy([
                        'rule' => $rule,
                        'deleted' => 0,
                        'globalStatus' => ['Open', 'Error'],
                    ]);
                // Return to the view detail fo the rule if we found a document open or in error
                if (!empty($docErrorOpen)) {
                    if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
                        $session->set('warning', [$this->translator->trans('error.rule.edit_document_error_open_admin')]);
                    } else {
                        $session->set('error', [$this->translator->trans('error.rule.edit_document_error_open')]);

                        return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
                    }
                }

                $this->sessionService->setParamRuleLastKey($rule->getId());
                $key = $this->sessionService->getParamRuleLastKey();
                //--
                // si une session existe alors on la supprime
                if ($this->sessionService->isParamRuleExist($key)) {
                    $this->sessionService->removeParamRule($key);
                }

                // préparation des sessions
                if (!empty($rule->getDeleted())) {
                    $session->set('error', [$this->translator->trans('error.rule.edit_rule_deleted')]);

                    return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
                }

                // composition des sessions
                $this->sessionService->setParamRuleNameValid($key, true);
                $this->sessionService->setParamRuleName($key, $rule->getName());
                $this->sessionService->setParamRuleConnectorSourceId($key, (string) $rule->getConnectorSource()->getId());
                $this->sessionService->setParamRuleConnectorCibleId($key, (string) $rule->getConnectorTarget()->getId());
                $this->sessionService->setParamRuleLastId($key, $rule->getId());

                // Connector source -------------------
                $connectorParamsSource = $rule->getConnectorSource();
                $this->sessionService->setParamRuleSourceSolution($key, $rule->getConnectorSource()->getSolution()->getName());

                foreach ($connectorParamsSource as $connector) {
                    $this->sessionService->setParamRuleSourceConnector($key, $connector->getName(), $connector->getValue());
                }
                // Connector source -------------------

                // Connector target -------------------
                $connectorParamsTarget = $rule->getConnectorTarget();
                $this->sessionService->setParamRuleCibleSolution($key, $rule->getConnectorTarget()->getSolution()->getName());

                foreach ($connectorParamsTarget as $connector) {
                    $this->sessionService->setParamRuleCibleConnector($key, $connector->getName(), $connector->getValue());
                }
                // Connector target -------------------

                // Paramètre d'une règle
                if ($rule->getParams()->count()) {
                    $params = [];
                    foreach ($rule->getParams() as $ruleParamsObj) {
                        $params[] = [
                            'name' => $ruleParamsObj->getName(),
                            'value' => $ruleParamsObj->getValue(),
                        ];
                    }
                    $this->sessionService->setParamRuleReloadParams($key, $params);
                }

                // Modules --
                $this->sessionService->setParamRuleSourceModule($key, $rule->getModuleSource());
                $this->sessionService->setParamRuleCibleModule($key, $rule->getModuletarget());
                // Modules --

                // reload ---------------
                $ruleFields = $rule->getFields();

                // get_modules_fields en source pour avoir l'association fieldid / libellé (ticket 548)
                $solution_source_nom = $this->sessionService->getParamRuleSourceSolution($key);
                $solution_source = $this->solutionManager->get($solution_source_nom);

                $login = $solution_source->login($this->decrypt_params($this->sessionService->getParamRuleSource($key)));
                if (empty($solution_source->connexion_valide)) {
                    throw new Exception('failed to login to the source application .'.(!empty($login['error']) ? $login['error'] : ''));
                }

                // SOURCE ----- Récupère la liste des champs source
                // O récupère le module de la règle
                $sourceModule = $rule->getModuleSource();
                $sourceFieldsInfo = $solution_source->get_module_fields($sourceModule);

                // Champs et formules d'une règle
                if ($ruleFields) {
                    foreach ($ruleFields as $ruleFieldsObj) {
                        $array = [
                            'target' => $ruleFieldsObj->getTarget(),
                            'source' => [],
                            'formula' => $ruleFieldsObj->getFormula(),
                        ];
                        $fields_source = explode(';', $ruleFieldsObj->getSource());

                        if (!empty($fields_source)) {
                            foreach ($fields_source as $field_source) {
                                if ('my_value' == $field_source) {
                                    $array['source'][$field_source] = 'my_value';
                                } elseif (isset($sourceFieldsInfo[$field_source])) {
                                    $array['source'][$field_source] = $sourceFieldsInfo[$field_source]['label'];
                                } else {
                                    if (!empty($sourceFieldsInfo)) {
                                        foreach ($sourceFieldsInfo as $multiModule) {
                                            if (isset($multiModule[$field_source])) {
                                                $array['source'][$field_source] = $multiModule[$field_source]['label'];
                                            }
                                        }
                                    }
                                }
                                if (!isset($array['source'][$field_source])) {
                                    throw new Exception('failed to get the field '.$field_source);
                                }
                            }
                            $fields[] = $array;
                        }
                    }
                    $this->sessionService->setParamRuleReloadFields($key, $fields);
                }

                // Relations d'une règle
                if ($rule->getRelationsShip()->count()) {
                    foreach ($rule->getRelationsShip() as $ruleRelationShipsObj) {
                        $relate[] = [
                            'source' => $ruleRelationShipsObj->getFieldNameSource(),
                            'target' => $ruleRelationShipsObj->getFieldNameTarget(),
                            'id' => $ruleRelationShipsObj->getFieldId(),
                            'parent' => $ruleRelationShipsObj->getParent(),
                        ];
                    }
                    $this->sessionService->setParamRuleReloadRelate($key, $relate);
                }

                // Filter
                if ($rule->getFilters()->count()) {
                    foreach ($rule->getFilters() as $ruleFilters) {
                        $filter[] = [
                            'target' => $ruleFilters->getTarget(),
                            'type' => $ruleFilters->getType(),
                            'value' => $ruleFilters->getValue(),
                        ];
                    }
                }

                $this->sessionService->setParamRuleReloadFilter($key, ((isset($filter)) ? $filter : []));

                // reload ---------------
                return $this->redirect($this->generateUrl('regle_stepthree', ['id' => $rule->getId()]));
            } catch (Exception $e) {
                $this->sessionService->setCreateRuleError($key, $this->translator->trans('error.rule.update').' '.$e->getMessage());
                $session->set('error', [$this->translator->trans('error.rule.update').' '.$e->getMessage()]);

                return $this->redirect($this->generateUrl('regle_open', ['id' => $rule->getId()]));
            }
        }

        /**
         * FICHE D'UNE REGLE.
         *
         * @param $id
         *
         * @return Response
         *
         * @Route("/view/{id}", name="regle_open")
         */
        public function ruleOpenAction($id)
        {
            if ($this->getUser()->isAdmin()) {
                $list_fields_sql = ['id' => $id];
            } else {
                $list_fields_sql =
                    ['id' => $id,
                        'createdBy' => $this->getUser()->getId(),
                    ];
            }
            // Detecte si la session est le support ---------

            // Infos de la regle
            /** @var Rule $rule */
            $rule = $this->entityManager->getRepository(Rule::class)->findOneBy($list_fields_sql);
            if (!$rule) {
                throw $this->createNotFoundException('La fiche n existe pas dans la base de donnees');
            }

            // Liste des relations
            $rule_relationships = $rule->getRelationsShip();
            $solution_cible_nom = $rule->getConnectorTarget()->getSolution()->getName();
            $solution_cible = $this->solutionManager->get($solution_cible_nom);
            $moduleCible = (string) $rule->getModuleTarget();

            $tab_rs = [];
            $i = 0;
            foreach ($rule_relationships as $r) {
                $tab_rs[$i]['getFieldId'] = $r->getFieldId();
                $tab_rs[$i]['getFieldNameSource'] = $r->getFieldNameSource();
                $tab_rs[$i]['getFieldNameTarget'] = $r->getFieldNameTarget();
                $tab_rs[$i]['getParent'] = $r->getParent();

                $ruleTmp = $this->entityManager->getRepository(Rule::class)
                    ->findOneBy([
                        'id' => $r->getFieldId(),
                    ]
                    );

                $tab_rs[$i]['getName'] = $ruleTmp->getName();
                ++$i;
            }

            // Infos connecteurs & solutions
            $connector = $this->entityManager->getRepository(Rule::class)
                ->infosConnectorByRule($rule->getId());

            // Changement de référence pour certaines solutions
            $autorization_source = $connector[0]['solution_source'];
            $autorization_module_trans = mb_strtolower($rule->getModuleSource());

            $Params = $rule->getParams();
            $Fields = $rule->getFields();
            $Filters = $rule->getFilters();
            $ruleParam = RuleManager::getFieldsParamView();
            $params_suite = false;
            if ($Params) {
                foreach ($Params as $field) {
                    $standardField = false;
                    foreach ($ruleParam as $index => $value) {
                        // Init the parameter in case it doesn't exist in the database yet
                        if (!isset($ruleParam[$index]['id_bdd'])) {
                            $ruleParam[$index]['id_bdd'] = '';
                            $ruleParam[$index]['value_bdd'] = '';
                        }
                        if ($field->getName() == $value['name']) {
                            $ruleParam[$index]['id_bdd'] = $field->getId();
                            $ruleParam[$index]['value_bdd'] = $field->getValue();
                            $standardField = true;
                            break;
                        }
                    }
                    if (!$standardField) {
                        if ('mode' == $field->getName()) {
                            // We send the translation of the mode to the view
                            switch ($field->getValue()) {
                                case '0':
                                    $params_suite['mode'] = $this->tools->getTranslation(['create_rule', 'step3', 'syncdata', 'create_modify']);
                                    break;
                                case 'C':
                                    $params_suite['mode'] = $this->tools->getTranslation(['create_rule', 'step3', 'syncdata', 'create_only']);
                                    break;
                                case 'S':
                                    $params_suite['mode'] = $this->tools->getTranslation(['create_rule', 'step3', 'syncdata', 'search_only']);
                                    break;
                                default:
                                    $params_suite['mode'] = $field->getValue();
                            }
                        } elseif ('bidirectional' == $field->getName()) {
                            if (!empty($field->getValue())) {
                                $ruleBidirectional = $this->entityManager->getRepository(Rule::class)
                                    ->findOneBy([
                                        'id' => $field->getValue(),
                                    ]
                                    );
                                // Send the name and the id of the opposite rule to the view
                                $params_suite['bidirectional'] = $field->getValue();
                                $params_suite['bidirectionalName'] = $ruleBidirectional->getName();
                            }
                        } else {
                            $params_suite['customParams'][] = ['name' => $field->getName(), 'value' => $field->getValue()];
                        }
                    }
                }
            }

            return $this->render('Rule/edit/fiche.html.twig', [
                'rule' => $rule,
                'connector' => $connector[0],
                'fields' => $Fields,
                'relate' => $tab_rs,
                'parentRelationships' => $solution_cible->allowParentRelationship($moduleCible),
                'params' => $ruleParam,
                'filters' => $Filters,
                'params_suite' => $params_suite,
                'id' => $id,
            ]
            );
        }

        /**
         * CREATION - STEP ONE - CONNEXION : jQuery ajax.
         *
         * @return JsonResponse|Response
         *
         * @Route("/inputs", name="regle_inputs", methods={"POST"}, options={"expose"=true})
         */
        public function ruleInputsAction(Request $request)
        {
            $ruleKey = $this->sessionService->getParamRuleLastKey();

            // Retourne la liste des inputs pour la connexion
            if (1 == $request->request->get('mod')) {
                if (is_string($request->request->get('solution')) && is_string($request->request->get('parent'))) {
                    if (preg_match("#[\w]#", $request->request->get('solution')) && preg_match("#[\w]#", $request->request->get('parent'))) {
                        $classe = strtolower($request->request->get('solution'));

                        $parent = $request->request->get('parent');
                        $solution = $this->entityManager->getRepository(Solution::class)->findOneByName($classe);

                        $connector = new Connector();
                        $connector->setSolution($solution);

                        $fieldsLogin = [];
                        if (null !== $connector->getSolution()) {
                            $fieldsLogin = $this->solutionManager->get($connector->getSolution()->getName())->getFieldsLogin();
                        }

                        $form = $this->createForm(ConnectorType::class, $connector, [
                            'action' => $this->generateUrl('regle_connector_insert'),
                            'attr' => [
                                'fieldsLogin' => $fieldsLogin,
                                'secret' => $this->params->get('secret'),
                            ],
                        ]);

                        return $this->render('Ajax/result_liste_inputs.html.twig', [
                            'form' => $form->createView(),
                            'parent' => $parent,
                        ]
                        );
                    }
                }
            } // Vérifie si la connexion peut se faire ou non
            elseif (2 == $request->request->get('mod') || 3 == $request->request->get('mod')) {
                // Connector
                if (2 == $request->request->get('mod')) {
                    if (preg_match("#[\w]#", $request->request->get('champs')) && preg_match("#[\w]#", $request->request->get('parent')) && preg_match("#[\w]#", $request->request->get('solution'))) {
                        $classe = strtolower($request->request->get('solution'));
                        $solution = $this->solutionManager->get($classe);

                        // établi un tableau params
                        $champs = explode(';', $request->request->get('champs'));

                        if ($champs) {
                            foreach ($champs as $key) {
                                $input = explode('::', $key);
                                if (!empty($input[0])) {
                                    if (!empty($input[1]) || is_numeric($input[1])) {
                                        $param[$input[0]] = trim($input[1]);
                                        $this->sessionService->setParamConnectorParentType($request->request->get('parent'), $input[0], trim($input[1]));
                                    }
                                }
                            }
                        }
                        $this->sessionService->setParamConnectorParentType($request->request->get('parent'), 'solution', $classe);

                        // Vérification du nombre de champs
                        if (isset($param) && (count($param) == count($solution->getFieldsLogin()))) {
                            $result = $solution->login($param);
                            $r = $solution->connexion_valide;

                            if (!empty($r)) {
                                return new JsonResponse(['success' => true]); // Connexion valide
                            }
                            $this->sessionService->removeParamRule($ruleKey);

                            return new JsonResponse(['success' => false, 'message' => $this->translator->trans($result['error'])]); // Erreur de connexion
                        }

                        return new JsonResponse(['success' => false, 'message' => $this->translator->trans('Connection error')]); // Erreur pas le même nombre de champs
                    }
                } // Rule
                elseif (3 == $request->request->get('mod')) {
                    // 0 : solution
                    // 1 : id connector
                    $params = explode('_', $request->request->get('solution'));

                    // Deux params obligatoires
                    if (2 == count($params) && intval($params[1]) && is_string($params[0])) {
                        $this->sessionService->removeParamParentRule($ruleKey, $request->request->get('parent'));
                        $classe = strtolower($params[0]);
                        $solution = $this->solutionManager->get($classe);

                        $connector = $this->getDoctrine()
                            ->getManager()
                            ->getRepository(Connector::class)
                            ->findOneById($params[1]);

                        $connector_params = $this->getDoctrine()
                            ->getManager()
                            ->getRepository(ConnectorParam::class)
                            ->findByConnector($connector);

                        if ($connector_params) {
                            foreach ($connector_params as $key) {
                                $this->sessionService->setParamConnectorParentType($request->request->get('parent'), $key->getName(), $key->getValue());
                            }
                        }

                        $this->sessionService->setParamRuleName($ruleKey, $request->request->get('name'));

                        // Affectation id connector
                        $this->sessionService->setParamRuleConnectorParent($ruleKey, $request->request->get('parent'), $params[1]);
                        //$myddlewareSession['obj'][$request->request->get('parent')] = $connector_params;

                        $result = $solution->login($this->decrypt_params($this->sessionService->getParamParentRule($request->request->get('parent'))));
                        $this->sessionService->setParamRuleParentName($ruleKey, $request->request->get('parent'), 'solution', $classe);

                        $r = $solution->connexion_valide;
                        if (!empty($r)) {
                            return new JsonResponse(['success' => true]); // Connexion valide
                        }

                        return new JsonResponse(['success' => false, 'message' => $this->translator->trans($result['error'])]); // Erreur de connexion

                        exit;

                        return $this->render('Ajax/result_connexion.html.twig', []
                        );
                    }

                    return new JsonResponse(['success' => false, 'message' => $this->translator->trans('Connection error')]);
                }
            } else {
                throw $this->createNotFoundException('Error');
            }
        }

        /**
         * CREATION - STEP ONE - VERIF ALIAS RULE.
         *
         * @return JsonResponse
         *
         * @Route("/inputs/name_unique/", name="regle_inputs_name_unique", methods={"POST"}, options={"expose"=true})
         */
        public function ruleNameUniqAction(Request $request)
        {
            $key = $this->sessionService->getParamRuleLastKey();

            if ('POST' == $request->getMethod()) {
                $this->getInstanceBdd();

                // Cherche si la règle existe en fonction de son nom
                $rule = $this->entityManager->getRepository(Rule::class)
                    ->findOneBy([
                        'name' => $request->request->get('name'),
                    ]
                    );

                // 0 existe pas 1 existe
                if (null == $rule) {
                    $existRule = 0;
                    $this->sessionService->setParamRuleNameValid($key, true);
                    $this->sessionService->setParamRuleName($key, $request->request->get('name'));
                } else {
                    $existRule = 1;
                    $this->sessionService->setParamRuleNameValid($key, false);
                }

                return new JsonResponse($existRule);
            }
            throw $this->createNotFoundException('Error');
        }

        /**
         * CREATION - STEP TWO - CHOIX MODULES.
         *
         * @return RedirectResponse|Response
         *
         * @Route("/create/step2/", name="regle_steptwo", methods={"POST"})
         */
        public function ruleStepTwoAction(Request $request)
        {
            $session = $request->getSession();
            $myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
            // We always add data again in session because these data are removed after the call of the get
            $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
            // si le nom de la règle est inferieur à 3 caractères :
            if (!isset($myddlewareSession['param']['rule']['source']['solution']) || strlen($myddlewareSession['param']['rule']['rulename']) < 3 || false == $myddlewareSession['param']['rule']['rulename_valide']) {
                $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.valid');
                $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            try {
                // ---------------- SOURCE ----------------------------
                $solution_source_nom = $myddlewareSession['param']['rule']['source']['solution'];
                $solution_source = $this->solutionManager->get($solution_source_nom);

                $sourceConnection = $solution_source->login($this->decrypt_params($myddlewareSession['param']['rule']['source']));

                if (empty($solution_source->connexion_valide)) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.source_module_connect').' '.(!empty($sourceConnection['error']) ? $sourceConnection['error'] : 'No message returned by '.$solution_source_nom);
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                $liste_modules_source = tools::composeListHtml($solution_source->get_modules('source'), $this->translator->trans('create_rule.step2.choice_module'));
                if (!$liste_modules_source) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.source_module_load_list');
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                // ---------------- /SOURCE ----------------------------
            } catch (Exception $e) {
                $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.source_module_all');
                $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            try {
                // ---------------- TARGET ----------------------------
                // Si la solution est la même que la précèdente on récupère les infos
                if ($myddlewareSession['param']['rule']['source']['solution'] == $myddlewareSession['param']['rule']['cible']['solution']) {
                    $solution_cible = $solution_source;
                    $solution_cible_nom = $solution_source_nom;
                } else {
                    $solution_cible_nom = $myddlewareSession['param']['rule']['cible']['solution'];
                    $solution_cible = $this->solutionManager->get($solution_cible_nom);
                }
                $targetConnection = $solution_cible->login($this->decrypt_params($myddlewareSession['param']['rule']['cible']));

                if (empty($solution_cible->connexion_valide)) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.target_module_connect').' '.(!empty($targetConnection['error']) ? $targetConnection['error'] : 'No message returned by '.$solution_cible_nom);
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                $liste_modules_cible = tools::composeListHtml($solution_cible->get_modules('target'), $this->translator->trans('create_rule.step2.choice_module'));

                if (!$liste_modules_cible) {
                    $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.target_module_load_list');
                    $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }
                // ---------------- /TARGET ----------------------------
            } catch (Exception $e) {
                $myddlewareSession['error']['create_rule'] = $this->translator->trans('error.rule.target_module_all');
                $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            return $this->render('Rule/create/step2.html.twig', [
                'solution_source' => $solution_source_nom,
                'solution_cible' => $solution_cible_nom,
                'liste_modules_source' => $liste_modules_source,
                'liste_modules_cible' => $liste_modules_cible,
                'params' => $myddlewareSession['param']['rule'],
            ]
            );
        }

        /**
         * CREATION - STEP THREE - SIMULATION DES DONNEES.
         *
         * @return Response
         *
         * @Route("/create/step3/simulation/", name="regle_simulation", methods={"POST"})
         */
        public function ruleSimulationAction(Request $request)
        {
            $ruleKey = $this->sessionService->getParamRuleLastKey();

            if ('POST' == $request->getMethod() && $this->sessionService->isParamRuleExist($ruleKey)) {
                // retourne un tableau prêt à l'emploi
                $target = $this->createListeParamsRule(
                    $request->request->get('champs'), // Fields
                    $request->request->get('formules'), // Formula
                    '' // Params flux
                );

                $solution_source_nom = $this->sessionService->getParamRuleSourceSolution($ruleKey);
                $solution_source = $this->solutionManager->get($solution_source_nom);
                $solution_source->login($this->sessionService->getParamRuleSource($ruleKey));
                $tab_simulation = [];
                $sourcesfields = [];

                // récupération de tous les champs
                if (isset($target['fields']) && count($target['fields']) > 0) {
                    foreach ($target['fields'] as $f) {
                        if (isset($f)) {
                            foreach ($f as $name_fields_target => $k) {
                                if (isset($k['champs'])) {
                                    $sourcesfields = array_merge($k['champs'], $sourcesfields);
                                } else {
                                    $sourcesfields = $sourcesfields;
                                }
                            }
                        }
                    }
                } else {
                    // ici pour les règles avec des relations uniquement
                    return $this->render('Rule/create/onglets/simulation_tab.html.twig', [
                        'before' => [], // source
                        'after' => [], // target
                        'data_source' => false,
                    ]
                    );
                }

                // Add rule param if exist (the aren't exist in rule creation)
                $ruleParams = [];
                $ruleParamsResult = $this->getDoctrine()->getManager()->getRepository(RuleParam::class)->findByRule($ruleKey);
                if (!empty($ruleParamsResult)) {
                    foreach ($ruleParamsResult as $ruleParamsObj) {
                        $ruleParams[$ruleParamsObj->getName()] = $ruleParamsObj->getValue();
                    }
                }

                // Get source data
                $source = $solution_source->read_last([
                    'module' => $this->sessionService->getParamRuleSourceModule($ruleKey),
                    'fields' => $sourcesfields,
                    'ruleParams' => $ruleParams, ]);

                if (isset($source['done'])) {
                    $before = [];
                    $after = [];
                    if ($source['done']) {
                        foreach ($target['fields'] as $f) {
                            foreach ($f as $name_fields_target => $k) {
                                $r['after'] = [];
                                // Préparation pour transformation
                                $name = trim($name_fields_target);
                                $target_fields = [
                                    'target_field_name' => $name,
                                    'source_field_name' => ((isset($k['champs'])) ? implode(';', $k['champs']) : 'my_value'),
                                    'formula' => ((isset($k['formule'][0]) ? $k['formule'][0] : '')),
                                    'related_rule' => '',
                                ];

                                // Transformation
                                $response = $this->documentManager->getTransformValue($source['values'], $target_fields);
                                if (!isset($response['message'])) {
                                    $r['after'][$name_fields_target] = $this->documentManager->getTransformValue($source['values'], $target_fields);
                                }
                                // If error during transformation, we send back the error
                                if (
                                    null == $r['after'][$name_fields_target]
                                    and !empty($response['message'])
                                ) {
                                    $r['after'][$name_fields_target] = $response['message'];
                                }

                                $k['fields'] = [];
                                if (empty($k['champs'])) {
                                    $k['fields']['Formula'] = ((isset($k['formule'][0]) ? $k['formule'][0] : ''));
                                } else {
                                    foreach ($k['champs'] as $fields) {
                                        // Fields couldn't be return. For example Magento return only field not empty
                                        if (!empty($source['values'][$fields])) {
                                            $k['fields'][$fields] = $source['values'][$fields];
                                        } else {
                                            $k['fields'][$fields] = '';
                                        }
                                    }
                                }

                                $tab_simulation[] = [
                                    'after' => $r['after'],
                                    'before' => $k['fields'],
                                ];
                            }
                        }
                        $after = [];
                        // Préparation pour tableau template
                        foreach ($tab_simulation as $key => $value) {
                            foreach ($value as $k => $v) {
                                if ('before' == $k) {
                                    $before[] = $v;
                                } else {
                                    $after[] = $v;
                                }
                            }
                        }
                    }
                }

                return $this->render('Rule/create/onglets/simulation_tab.html.twig', [
                    'before' => $before, // source
                    'after' => $after, // target
                    'data_source' => $source['done'],
                    'params' => $this->sessionService->getParamRule($ruleKey),
                ]
                );
            }
            throw $this->createNotFoundException('Error');
        }

        /**
         * CREATION - STEP THREE - CHOIX DES CHAMPS - MAPPING DES CHAMPS.
         *
         * @return RedirectResponse|Response
         *
         * @Route("/create/step3/{id}", name="regle_stepthree", defaults={"id"=0})
         */
        public function ruleStepThreeAction(Request $request)
        {
            $this->getInstanceBdd();
            $ruleKey = $request->get('id');

            // Test que l'ordre des étapes
            if (!$this->sessionService->isParamRuleExist($ruleKey)) {
                $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.order'));

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            // Contrôle si la nouvelle règle peut-être valide
            if ($this->sessionService->isRuleNameLessThanXCharacters($ruleKey, 3)) {
                $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.valid'));

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }

            try {
                // ---- Mode update ----
                if (!$this->sessionService->isParamRuleSourceModuleExist($ruleKey) && !$this->sessionService->isParamRuleCibleModuleExist($ruleKey)) {
                    // RELOAD : Chargement des données d'une règle en édition
                    $this->sessionService->setParamRuleSourceModule($ruleKey, $request->request->get('source_module'));
                    $this->sessionService->setParamRuleCibleModule($ruleKey, $request->request->get('cible_module'));
                }
                // ---- Mode update ----

                // Get all data from the target solution first
                $solution_cible = $this->solutionManager->get($this->sessionService->getParamRuleCibleSolution($ruleKey));

                // TARGET ------------------------------------------------------------------
                // We retriev first all data from the target application and the from the source application
                // We can't do both solution in the same time because we could have a bug when these 2 solutions are the same (service are shared by default in Symfony)
                $targetConnection = $solution_cible->login($this->decrypt_params($this->sessionService->getParamRuleCible($ruleKey)));

                if (false == $solution_cible->connexion_valide) {
                    $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.target_module_connect').' '.(!empty($targetConnection['error']) ? $targetConnection['error'] : 'No message returned by '.$this->sessionService->getParamRuleCibleSolution($ruleKey)));

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }

                if ($request->request->get('cible_module')) {
                    $module['cible'] = $request->request->get('cible_module'); // mode create <<----
                } else {
                    $module['cible'] = $this->sessionService->getParamRuleCibleModule($ruleKey); // mode update <<----
                }

                // Récupère la liste des paramètres cible
                $rule_params_target = $solution_cible->getFieldsParamUpd('target', $module['cible']);

                // Récupère la liste des champs cible
                $rule_fields_target = $solution_cible->get_module_fields($module['cible'], 'target');

                // Récupération de tous les modes de règle possibles pour la cible et la source
                $targetMode = $solution_cible->getRuleMode($module['cible'], 'target');

                $fieldMappingAdd = $solution_cible->getFieldMappingAdd($module['cible']);

                // Liste des relations TARGET
                $relation = $solution_cible->get_module_fields_relate($module['cible'], '');

                $allowParentRelationship = $solution_cible->allowParentRelationship($this->sessionService->getParamRuleCibleModule($ruleKey));

                // Champs pour éviter les doublons
                $fieldsDuplicateTarget = $solution_cible->getFieldsDuplicate($this->sessionService->getParamRuleCibleModule($ruleKey));

                // SOURCE ------------------------------------------------------------------
                // Connexion au service de la solution source
                $solution_source = $this->solutionManager->get($this->sessionService->getParamRuleSourceSolution($ruleKey));
                $sourceConnection = $solution_source->login($this->decrypt_params($this->sessionService->getParamRuleSource($ruleKey)));

                // Contrôle que la connexion est valide
                if (false == $solution_source->connexion_valide) {
                    $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.source_module_connect').' '.(!empty($sourceConnection['error']) ? $sourceConnection['error'] : 'No message returned by '.$this->sessionService->getParamRuleSourceSolution($ruleKey)));

                    return $this->redirect($this->generateUrl('regle_stepone_animation'));
                    exit;
                }
                $modules = $solution_source->get_modules('source');
                if ($request->request->get('source_module')) {
                    $module['source'] = $request->request->get('source_module'); // mode create <<----
                } else {
                    $module['source'] = $this->sessionService->getParamRuleSourceModule($ruleKey); // mode update <<----
                }

                // Met en mémoire la façon de traiter la date de référence
                $this->sessionService->setParamRuleSourceDateReference($ruleKey, $solution_source->referenceIsDate($module['source']));

                // Ajoute des champs source pour la validation
                $rule_params_source = $solution_source->getFieldsParamUpd('source', $module['source']);

                // Récupère la liste des champs source
                $rule_fields_source = $solution_source->get_module_fields($module['source'], 'source');

                if ($rule_fields_source) {
                    $this->sessionService->setParamRuleSourceFields($ruleKey, $rule_fields_source);

                    // Erreur champs, pas de données sources (Exemple: GotoWebinar)

                    if ($this->sessionService->isParamRuleSourceFieldsErrorExist($ruleKey) && null != $this->sessionService->getParamRuleSourceFieldsError($ruleKey)) {
                        $this->sessionService->setCreateRuleError($ruleKey, $this->sessionService->getParamRuleSourceFieldsError($ruleKey));

                        return $this->redirect($this->generateUrl('regle_stepone_animation'));
                        exit;
                    }

                    foreach ($rule_fields_source as $t => $k) {
                        $source['table'][$module['source']][$t] = $k['label'];
                    }
                    // Tri des champs sans tenir compte de la casse
                    ksort($source['table'][$module['source']], SORT_NATURAL | SORT_FLAG_CASE);
                }

                // SOURCE ----- Récupère la liste des champs source

                // Type de synchronisation
                // Récupération de tous les modes de règle possibles pour la source
                $sourceMode = $solution_source->getRuleMode($module['source'], 'source');
                // Si la target à le type S (search) alors on l'ajoute à la source pour qu'il soit préservé par l'intersection
                if (array_key_exists('S', $targetMode)) {
                    $sourceMode['S'] = 'search_only';
                }
                $intersectMode = array_intersect($targetMode, $sourceMode);
                // Si jamais l'intersection venait à être vide (ce qui ne devrait jamais arriver) on met par défaut le mode CREATE
                if (empty($intersectMode)) {
                    $intersectMode['C'] = 'create_only';
                }
                // If duplicate field exist for the target solution, we allow search rule type
                if (!empty($fieldsDuplicateTarget)) {
                    $intersectMode['S'] = 'search_only';
                }
                $this->sessionService->setParamRuleCibleMode($ruleKey, $intersectMode);

                // Préparation des champs cible
                $cible['table'] = [];

                if ($rule_fields_target) {
                    $this->sessionService->setParamRuleTargetFields($ruleKey, $rule_fields_target);

                    $tmp = $rule_fields_target;

                    $normal = [];
                    $required = [];
                    foreach ($rule_fields_target as $t => $k) {
                        if (isset($k['required']) && true == $k['required']) {
                            $required[] = $t;
                        } else {
                            $normal[] = $t;
                        }
                    }

                    asort($required);
                    asort($normal);

                    $alpha = array_merge($required, $normal);
                    $field_target_alpha = [];
                    foreach ($alpha as $name_fields) {
                        $field_target_alpha[$name_fields] = $tmp[$name_fields]['required'];
                    }

                    $cible['table'][$module['cible']] = $field_target_alpha;
                } else {
                    $cible['table'][$module['cible']] = []; // rev 1.1.1
                }

                // On ajoute des champs personnalisés à notre mapping
                if ($fieldMappingAdd && $this->sessionService->isParamRuleLastVersionIdExist($ruleKey)) {
                    $ruleFields = $this->getDoctrine()
                        ->getManager()
                        ->getRepository(RuleField::class)
                        ->findByRule($this->sessionService->getParamRuleLastId($ruleKey));

                    $tmp = [];
                    foreach ($ruleFields as $fields) {
                        $tmp[$fields->getTarget()] = 0;
                    }

                    foreach ($cible['table'][$module['cible']] as $k => $value) {
                        $tmp[$k] = $value;
                    }

                    $cible['table'][$module['cible']] = $tmp;

                    ksort($cible['table'][$module['cible']]);
                }

                // -------------------	TARGET
                $lst_relation_target = [];
                $lst_relation_target_alpha = [];
                if ($relation) {
                    foreach ($relation as $key => $value) {
                        $lst_relation_target[] = $key;
                    }

                    asort($lst_relation_target);

                    foreach ($lst_relation_target as $name_relate) {
                        $lst_relation_target_alpha[$name_relate]['required'] = $relation[$name_relate]['required_relationship'];
                        $lst_relation_target_alpha[$name_relate]['name'] = $name_relate;
                        $lst_relation_target_alpha[$name_relate]['label'] = (!empty($relation[$name_relate]['label']) ? $relation[$name_relate]['label'] : $name_relate);
                    }
                }

                // -------------------	SOURCE
                // Liste des relations SOURCE
                // Add parameters to be able to read rules linked in function get_module_fields_relate (used for database connector for example)
                $param['connectorSourceId'] = $this->sessionService->getParamRuleConnectorSourceId($ruleKey);
                $param['connectorTargetId'] = $this->sessionService->getParamRuleConnectorCibleId($ruleKey);
                $param['ruleName'] = $this->sessionService->getParamRuleName($ruleKey);
                $relation_source = $solution_source->get_module_fields_relate($this->sessionService->getParamRuleSourceModule($ruleKey), $param);
                $lst_relation_source = [];
                $lst_relation_source_alpha = [];
                $choice_source = [];
                if ($relation_source) {
                    foreach ($relation_source as $key => $value) {
                        $lst_relation_source[] = $key;
                    }

                    asort($lst_relation_source);
                    foreach ($lst_relation_source as $name_relate) {
                        $lst_relation_source_alpha[$name_relate]['label'] = $relation_source[$name_relate]['label'];
                    }

                    // préparation de la liste en html
                    foreach ($lst_relation_source_alpha as $key => $value) {
                        $choice_source[$key] = (!empty($value['label']) ? $value['label'] : $key);
                    }
                }

                if (!isset($source['table'])) {
                    $source['table'][$this->sessionService->getParamRuleSourceModule($ruleKey)] = [];
                }

                // -- Relation
                // Rule list with the same connectors (both directions) to get the relate ones
                $ruleListRelation = $this->getDoctrine()->getManager()->getRepository(Rule::class)->createQueryBuilder('r')
                    ->select('r.id, r.name, r.moduleSource')
                    ->where('(
												r.connectorSource= ?1 
											AND r.connectorTarget= ?2
											AND r.name != ?3
											AND r.deleted = 0
										)
									OR (
												r.connectorTarget= ?1
											AND r.connectorSource= ?2
											AND r.name != ?3
											AND r.deleted = 0
									)')
                    ->setParameter(1, (int) $this->sessionService->getParamRuleConnectorSourceId($ruleKey))
                    ->setParameter(2, (int) $this->sessionService->getParamRuleConnectorCibleId($ruleKey))
                    ->setParameter(3, $this->sessionService->getParamRuleName($ruleKey))
                    ->getQuery()
                    ->getResult();

                //Verson 1.1.1 : possibilité d'ajouter des relations custom en fonction du module source
                $ruleListRelationSourceCustom = $solution_source->get_rule_custom_relationship($this->sessionService->getParamRuleSourceModule($ruleKey), 'source');
                if (!empty($ruleListRelationSourceCustom)) {
                    $ruleListRelation = array_merge($ruleListRelation, $ruleListRelationSourceCustom);
                }

                $choice = [];
                $control = [];

                foreach ($ruleListRelation as $key => $value) {
                    if (!in_array($value['name'], $control)) {
                        $choice[$value['id']] = $value['name'];
                        $control[] = $value['name'];
                    }
                }

                asort($choice);

                // -------------------	Parent relation
                // Search if we can send document merged with the target solution
                $lstParentFields = [];
                if ($allowParentRelationship) {
                    if (!empty($ruleListRelation)) {
                        // We get all relate fields from every source module
                        foreach ($ruleListRelation as $ruleRelation) {
                            // Get the relate fields from the source module of related rules
                            $rule_fields_source = $solution_source->get_module_fields($ruleRelation['moduleSource'], 'source');
                            $sourceRelateFields = $solution_source->get_module_fields_relate($ruleRelation['moduleSource'], '');
                            if (!empty($sourceRelateFields)) {
                                foreach ($sourceRelateFields as $key => $sourceRelateField) {
                                    $lstParentFields[$key] = $sourceRelateField['label'];
                                }
                            }
                        }
                        // We allow  to search by the id of the module
                        $lstParentFields['Myddleware_element_id'] = $this->translator->trans('create_rule.step3.relation.record_id');
                    }
                    // No parent relation if no rule to link or no fields related
                    if (empty($lstParentFields)) {
                        $allowParentRelationship = false;
                    }
                }

                // On récupére l'EntityManager
                $this->getInstanceBdd();

                // Récupère toutes les catégories
                $lstCategory = $this->entityManager->getRepository(FuncCat::class)
                    ->findAll();

                // Récupère toutes les functions
                $lstFunctions = $this->entityManager->getRepository(Functions::class)
                    ->findAll();

                // Les filtres
                $lst_filter = [
                    'content' => $this->translator->trans('filter.content'),
                    'notcontent' => $this->translator->trans('filter.notcontent'),
                    'begin' => $this->translator->trans('filter.begin'),
                    'end' => $this->translator->trans('filter.end'),
                    'gt' => $this->translator->trans('filter.gt'),
                    'lt' => $this->translator->trans('filter.lt'),
                    'equal' => $this->translator->trans('filter.equal'),
                    'different' => $this->translator->trans('filter.different'),
                    'gteq' => $this->translator->trans('filter.gteq'),
                    'lteq' => $this->translator->trans('filter.lteq'),
                    'in' => $this->translator->trans('filter.in'),
                    'notin' => $this->translator->trans('filter.notin'),
                ];

                // paramètres de la règle
                $rule_params = array_merge($rule_params_source, $rule_params_target);

                // récupération des champs de type liste --------------------------------------------------

                // -----[ SOURCE ]-----
                if ($this->sessionService->isParamRuleSourceFieldsExist($ruleKey)) {
                    foreach ($this->sessionService->getParamRuleSourceFields($ruleKey) as $field => $fields_tab) {
                        if (array_key_exists('option', $fields_tab)) {
                            $formule_list['source'][$field] = $fields_tab;
                        }
                    }
                }

                if (isset($formule_list['source']) && count($formule_list['source']) > 0) {
                    foreach ($formule_list['source'] as $field => $fields_tab) {
                        foreach ($fields_tab['option'] as $field_name => $fields) {
                            if (!empty($fields)) {
                                $formule_list['source'][$field]['option'][$field_name] = $field_name.' ( '.$fields.' )';
                            }
                        }
                    }
                }

                $html_list_source = '';
                if (isset($formule_list['source'])) {
                    foreach ($formule_list['source'] as $field => $fields_tab) {
                        $html_list_source .= '<optgroup label="'.$field.'">';
                        $html_list_source .= tools::composeListHtml($fields_tab['option']);
                        $html_list_source .= '</optgroup>';
                    }
                }

                // -----[ TARGET ]-----
                if ($this->sessionService->isParamRuleTargetFieldsExist($ruleKey)) {
                    foreach ($this->sessionService->getParamRuleTargetFields($ruleKey) as $field => $fields_tab) {
                        if (array_key_exists('option', $fields_tab)) {
                            $formule_list['target'][$field] = $fields_tab;
                        }
                    }
                }

                if (isset($formule_list['target']) && count($formule_list['target']) > 0) {
                    foreach ($formule_list['target'] as $field => $fields_tab) {
                        foreach ($fields_tab['option'] as $field_name => $fields) {
                            if (!empty($fields)) {
                                $formule_list['target'][$field]['option'][$field_name] = $field_name.' ( '.$fields.' )';
                            }
                        }
                    }
                }

                $html_list_target = '';
                if (isset($formule_list['target'])) {
                    foreach ($formule_list['target'] as $field => $fields_tab) {
                        $html_list_target .= '<optgroup label="'.$field.'">';
                        $html_list_target .= tools::composeListHtml($fields_tab['option']);
                        $html_list_target .= '</optgroup>';
                    }
                }

                // récupération des champs de type liste --------------------------------------------------

                // Type de synchronisation de données rev 1.06 --------------------------
                if ($this->sessionService->isParamRuleCibleModuleExist($ruleKey)) {
                    $mode_translate = [];
                    foreach ($this->sessionService->getParamRuleCibleMode($ruleKey) as $key => $value) {
                        $mode_translate[$key] = $this->translator->trans('create_rule.step3.syncdata.'.$value);
                    }

                    $mode =
                        [
                            [
                                'id' => 'mode',
                                'name' => 'mode',
                                'required' => false,
                                'type' => 'option',
                                'label' => $this->translator->trans('create_rule.step3.syncdata.label'),
                                'option' => $mode_translate,
                            ],
                        ];

                    $rule_params = array_merge($rule_params, $mode);
                }
                // Type de synchronisation de données rev 1.06 --------------------------

                //  rev 1.07 --------------------------
                $bidirectional_params['connector']['source'] = $this->sessionService->getParamRuleConnectorSourceId($ruleKey);
                $bidirectional_params['connector']['cible'] = $this->sessionService->getParamRuleConnectorCibleId($ruleKey);
                $bidirectional_params['module']['source'] = $module['source'];
                $bidirectional_params['module']['cible'] = $module['cible'];

                $bidirectional = RuleClass::getBidirectionalRules($this->connection, $bidirectional_params);
                if ($bidirectional) {
                    $rule_params = array_merge($rule_params, $bidirectional);
                }

                // Add param to allow deletion (need source and target application ok to enable deletion)
                if (
                    true == $solution_source->getReadDeletion($module['source'])
                    and true == $solution_cible->getSendDeletion($module['cible'])
                ) {
                    $deletion = [
                        [
                            'id' => 'deletion',
                            'name' => 'deletion',
                            'required' => false,
                            'type' => 'option',
                            'label' => $this->translator->trans('create_rule.step3.deletion.label'),
                            'option' => [0 => '', 1 => $this->translator->trans('create_rule.step3.deletion.yes')],
                        ],
                    ];
                    $rule_params = array_merge($rule_params, $deletion);
                } else {
                    // If the deletion is disable (database in source OK but target application non OK), we remove the deletion list field of database connector
                    $keyDeletionField = array_search('deletionField', array_column($rule_params, 'id'));
                    if (!empty($keyDeletionField)) {
                        unset($rule_params[$keyDeletionField]);
                    }
                }

                //  rev 1.07 --------------------------
                $result = [
                    'source' => $source['table'],
                    'cible' => $cible['table'],
                    'rule_params' => $rule_params,
                    'lst_relation_target' => $lst_relation_target_alpha,
                    'lst_relation_source' => $choice_source,
                    'lst_rule' => $choice,
                    'lst_category' => $lstCategory,
                    'lst_functions' => $lstFunctions,
                    'lst_filter' => $lst_filter,
                    'params' => $this->sessionService->getParamRule($ruleKey),
                    'duplicate_target' => $fieldsDuplicateTarget,
                    'opt_target' => $html_list_target,
                    'opt_source' => $html_list_source,
                    'fieldMappingAddListType' => $fieldMappingAdd,
                    'parentRelationships' => $allowParentRelationship,
                    'lst_parent_fields' => $lstParentFields,
                    'regleId' => $ruleKey,
                ];

                $result = $this->beforeRender($result);

                // Formatage des listes déroulantes :
                $result['lst_relation_source'] = tools::composeListHtml($result['lst_relation_source'], $this->translator->trans('create_rule.step3.relation.fields'));
                $result['lst_parent_fields'] = tools::composeListHtml($result['lst_parent_fields'], ' ');
                $result['lst_rule'] = tools::composeListHtml($result['lst_rule'], $this->translator->trans('create_rule.step3.relation.fields'));
                $result['lst_filter'] = tools::composeListHtml($result['lst_filter'], $this->translator->trans('create_rule.step3.relation.fields'));

                return $this->render('Rule/create/step3.html.twig', $result);

                // ----------------
            } catch (Exception $e) {
                $this->sessionService->setCreateRuleError($ruleKey, $this->translator->trans('error.rule.mapping').' : '.$e->getMessage().' ('.$e->getFile().' line '.$e->getLine().')');

                return $this->redirect($this->generateUrl('regle_stepone_animation'));
                exit;
            }
        }

        protected function beforeRender($result)
        {
            return $result;
        }

        /**
         * Indique des informations concernant le champ envoyé en paramètre.
         *
         * @param $field
         * @param $type
         *
         * @return Response
         *
         * @Route("/info/{type}/{field}/", name="path_info_field", methods={"GET"})
         * @Route("/info", name="path_info_field_not_param")
         */
        public function infoFieldAction(Request $request, $field, $type)
        {
            $session = $request->getSession();
            $myddlewareSession = $session->getBag('flashes')->get('myddlewareSession');
            // We always add data again in session because these data are removed after the call of the get
            $session->getBag('flashes')->set('myddlewareSession', $myddlewareSession);
            if (isset($field) && !empty($field) && isset($myddlewareSession['param']['rule']) && 'my_value' != $field) {
                if (isset($myddlewareSession['param']['rule'][$type]['fields'][$field])) {
                    return $this->render('Rule/create/onglets/info.html.twig', [
                        'field' => $myddlewareSession['param']['rule'][$type]['fields'][$field],
                        'name' => htmlentities(trim($field)),
                    ]
                    );
                }   // Possibilité de Mutlimodules
                foreach ($myddlewareSession['param']['rule'][$type]['fields'] as $subModule) { // Ce foreach fonctionnera toujours
                    if (isset($subModule[$field])) { // On teste si ça existe pour éviter une erreur PHP éventuelle
                        return $this->render('Rule/create/onglets/info.html.twig', [
                            'field' => $subModule[$field],
                            'name' => htmlentities(trim($field)),
                        ]
                        );
                    }
                }
                // On retourne vide si on l'a pas trouvé précédemment
                return $this->render('Rule/create/onglets/info.html.twig', [
                    'field' => '',
                ]
                );
            }

            return $this->render('Rule/create/onglets/info.html.twig', [
                'field' => '',
            ]
            );
        }

        /**
         * CREATION - STEP THREE - VERIF DES FORMULES.
         *
         * @return JsonResponse
         *
         * @Route("/create/step3/formula/", name="regle_formula", methods={"POST"})
         */
        public function ruleVerifFormulaAction(Request $request)
        {
            if ('POST' == $request->getMethod()) {
                // Mise en place des variables
                $this->formuleManager->init($request->request->get('formula')); // mise en place de la règle dans la classe
                $this->formuleManager->generateFormule(); // Genère la nouvelle formule à la forme PhP

                return new JsonResponse($this->formuleManager->parse['error']);
            }
            throw $this->createNotFoundException('Error');
        }

        /**
         * CREATION - STEP THREE - Validation du formulaire.
         *
         * @return JsonResponse
         *
         * @Route("/create/step3/validation/", name="regle_validation", methods={"POST"})
         */
        public function ruleValidationAction(Request $request)
        {
            // On récupére l'EntityManager
            $this->getInstanceBdd();
            $this->entityManager->getConnection()->beginTransaction();
            try {
                /*
                 * get rule id in the params in regle.js. In creation, regleId = 0
                 */
                if (!empty($request->request->get('params'))) {
                    foreach ($request->request->get('params') as $searchRuleId) {
                        if ('regleId' == $searchRuleId['name']) {
                            $ruleKey = $searchRuleId['value'];
                            break;
                        }
                    }
                }

                // retourne un tableau prêt à l'emploi
                $tab_new_rule = $this->createListeParamsRule(
                    $request->request->get('champs'), // Fields
                    $request->request->get('formules'), // Formula
                    $request->request->get('params') // Params
                );
                unset($tab_new_rule['params']['regleId']); // delete  id regle for gestion session

                // fields relate
                if (!empty($request->request->get('duplicate'))) {
                    // fix : Put the duplicate fields values in the old $tab_new_rule array
                    $duplicateArray = implode($request->request->get('duplicate'), ';');
                    $tab_new_rule['params']['rule']['duplicate_fields'] = $duplicateArray;
                    $this->sessionService->setParamParentRule($ruleKey, 'duplicate_fields', $duplicateArray);
                }
                // si le nom de la règle est inferieur à 3 caractères :
                if (strlen($this->sessionService->getParamRuleName($ruleKey)) < 3 || false == $this->sessionService->getParamRuleNameValid($ruleKey)) {
                    return new JsonResponse(0);
                }

                //------------ Create rule
                $connector_source = $this->getDoctrine()
                    ->getManager()
                    ->getRepository(Connector::class)
                    ->findOneById($this->sessionService->getParamRuleConnectorSourceId($ruleKey));

                $connector_target = $this->getDoctrine()
                    ->getManager()
                    ->getRepository(Connector::class)
                    ->findOneById($this->sessionService->getParamRuleConnectorCibleId($ruleKey));

                $param = RuleClass::getFieldsParamDefault();

                // Get the id of the rule if we edit a rule
                // Generate Rule object (create a new one or instanciate the existing one
                if (!$this->sessionService->isParamRuleLastVersionIdEmpty($ruleKey)) {
                    $oneRule = $this->entityManager->getRepository(Rule::class)->find($this->sessionService->getParamRuleLastId($ruleKey));
                    $oneRule->setDateModified(new \DateTime());
                    $oneRule->setModifiedBy($this->getUser()->getId());
                } else {
                    $oneRule = new Rule();
                    $oneRule->setConnectorSource($connector_source);
                    $oneRule->setConnectorTarget($connector_target);
                    $oneRule->setDateCreated(new \DateTime());
                    $oneRule->setDateModified(new \DateTime());
                    $oneRule->setCreatedBy($this->getUser()->getId());
                    $oneRule->setModifiedBy($this->getUser()->getId());
                    $oneRule->setModuleSource($this->sessionService->getParamRuleSourceModule($ruleKey));
                    $oneRule->setModuleTarget($this->sessionService->getParamRuleCibleModule($ruleKey));
                    $oneRule->setDeleted(0);
                    $oneRule->setActive((int) $param['active']);
                    $oneRule->setName($this->sessionService->getParamRuleName($ruleKey));
                }
                $this->entityManager->persist($oneRule);
                // On fait le flush pour obtenir le nameSlug. En cas de problème on fait un remove dans le catch
                $this->entityManager->flush();
                $this->sessionService->setRuleId($ruleKey, $oneRule->getId());
                $nameRule = $oneRule->getNameSlug();

                // BEFORE SAVE rev 1.08 ----------------------
                $relationshipsBeforeSave = $request->request->get('relations');
                $before_save = $this->ruleManager->beforeSave(
                    ['ruleName' => $nameRule,
                        'RuleId' => $oneRule->getId(),
                        'connector' => $this->sessionService->getParamParentRule($ruleKey, 'connector'),
                        'content' => $tab_new_rule,
                        'relationships' => $relationshipsBeforeSave,
                        'module' => [
                            'source' => [
                                'solution' => $this->sessionService->getParamRuleSourceSolution($ruleKey),
                                'name' => $this->sessionService->getParamRuleSourceModule($ruleKey),
                            ],
                            'target' => [
                                'solution' => $this->sessionService->getParamRuleCibleSolution($ruleKey),
                                'name' => $this->sessionService->getParamRuleCibleModule($ruleKey),
                            ],
                        ],
                    ]
                );
                if (!$before_save['done']) {
                    throw new Exception($before_save['message']);
                }
                // Si le retour du beforeSave contient des paramètres alors on les ajoute à la règle avant sauvegarde
                if (!empty($before_save['params'])) {
                    if (empty($tab_new_rule['params'])) {
                        $tab_new_rule['params'] = $before_save['params'];
                    } else {
                        $tab_new_rule['params'] = array_merge($tab_new_rule['params'], $before_save['params']);
                    }
                }

                // Check if search rule then duplicate field shouldn't be empty
                if (
                    'S' == $tab_new_rule['params']['mode']
                    and empty($tab_new_rule['params']['rule']['duplicate_fields'])
                ) {
                    throw new Exception($this->translator->trans('Failed to save the rule. If you choose to retrieve data with your rule, you have to select at least one duplicate field.'));
                }

                // Edit mode
                if (!$this->sessionService->isParamRuleLastVersionIdEmpty($ruleKey)) {
                    foreach ($oneRule->getFields() as $ruleField) {
                        $this->entityManager->remove($ruleField);
                        $this->entityManager->flush();
                    }

                    foreach ($oneRule->getRelationsShip() as $ruleRelationShip) {
                        $this->entityManager->remove($ruleRelationShip);
                        $this->entityManager->flush();
                    }

                    foreach ($oneRule->getFilters() as $ruleFilter) {
                        $this->entityManager->remove($ruleFilter);
                        $this->entityManager->flush();
                    }

                    // Rule Params
                    foreach ($oneRule->getParams() as $ruleParam) {
                        // Save reference date
                        if ('datereference' == $ruleParam->getName()) {
                            $date_reference = $ruleParam->getValue();
                        }
                        if (in_array($ruleParam->getName(), $this->standardRuleParam)) {
                            $this->entityManager->remove($ruleParam);
                            $this->entityManager->flush();
                        }
                    }
                } // Create mode
                else {
                    if ($this->sessionService->isParamRuleSourceDateReference($ruleKey) && $this->sessionService->getParamRuleSourceDateReference($ruleKey)) {
                        $date_reference = date('Y-m-d 00:00:00');
                    } else {
                        $date_reference = '';
                    }
                }

                //------------------------------- Create rule params -------------------
                if (isset($tab_new_rule['params']) || isset($param['RuleParam'])) {
                    if (!isset($tab_new_rule['params'])) {
                        $p = $param['RuleParam'];
                    } else {
                        $p = array_merge($param['RuleParam'], $tab_new_rule['params']);
                    }

                    $bidirectional = '';
                    foreach ($p as $key => $value) {
                        // Value could be empty, for bidirectional parameter for example (we don't test empty because mode could be equal 0)
                        if ('' == $value) {
                            continue;
                        }
                        $oneRuleParam = new RuleParam();
                        $oneRuleParam->setRule($oneRule);

                        // si tableau de doublon
                        if ('rule' == $key) {
                            $oneRuleParam->setName('duplicate_fields');
                            $oneRuleParam->setValue($value['duplicate_fields']);
                        } else {
                            $oneRuleParam->setName($key);
                            if ('datereference' == $key) {
                                // date de référence change en fonction create ou update
                                $oneRuleParam->setValue($date_reference);
                            } else {
                                $oneRuleParam->setValue($value);
                            }
                        }

                        // Save the parameter
                        if ('bidirectional' == $key) {
                            $bidirectional = $value;
                        }

                        $this->entityManager->persist($oneRuleParam);
                        $this->entityManager->flush();
                    }

                    // If a bidirectional parameter exist, we check if the opposite one exists too
                    if (!empty($bidirectional)) {
                        // Update the opposite rule if birectional rule
                        $ruleParamBidirectionalOpposite = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'rule' => $bidirectional,
                                'name' => 'bidirectional',
                                'value' => $oneRule->getId(),
                            ]);
                        // If the bidirectional parameter doesn't exist on the opposite rule we create it
                        if (empty($ruleParamBidirectionalOpposite)) {
                            $ruleParamBidirectionalOpposite = new RuleParam();
                            $ruleParamBidirectionalOpposite->setRule($bidirectional);
                            $ruleParamBidirectionalOpposite->setName('bidirectional');
                            $ruleParamBidirectionalOpposite->setValue($oneRule->getId());
                            $this->entityManager->persist($ruleParamBidirectionalOpposite);
                        }
                    } else {
                        // If no bidirectional parameter on the rule and if the bidirectional parametr exist on an opposite rule, we delete it
                        $ruleParamBidirectionalDelete = $this->entityManager->getRepository(RuleParam::class)
                            ->findOneBy([
                                'value' => $oneRule->getId(),
                                'name' => 'bidirectional',
                            ]);
                        if (!empty($ruleParamBidirectionalDelete)) {
                            $this->entityManager->remove($ruleParamBidirectionalDelete);
                            $this->entityManager->flush();
                        }
                    }
                }

                //------------------------------- Create rule fields -------------------
                $debug = [];

                if (isset($tab_new_rule['fields'])) {
                    foreach ($tab_new_rule['fields']['name'] as $field_target => $c) {
                        $field_source = '';
                        if (isset($c['champs'])) {
                            foreach ($c['champs'] as $name) {
                                $field_source .= $name.';';
                            }
                            $field_source = trim($field_source, ';');
                        }

                        // Formula
                        $formule = '';
                        if (isset($c['formule'])) {
                            foreach ($c['formule'] as $name) {
                                $formule .= $name.' ';
                                $debug[] = $name.' ';
                            }
                        }

                        // delete space
                        $field_source = str_replace(' ', '', $field_source);

                        // Insert
                        $oneRuleField = new RuleField();
                        $oneRuleField->setRule($oneRule);
                        $oneRuleField->setTarget(trim($field_target));
                        $oneRuleField->setSource(((!empty($field_source)) ? $field_source : 'my_value'));
                        $oneRuleField->setFormula(((!empty($formule)) ? trim($formule) : null));
                        $this->entityManager->persist($oneRuleField);
                        $this->entityManager->flush();
                    }
                }

                //------------------------------- RELATIONSHIPS -------------------
                $tabRelationShips = [];
                if (!is_null($request->request->get('relations'))) {
                    foreach ($request->request->get('relations') as $rel) {
                        if (
                            !empty($rel['rule'])
                            && !empty($rel['source'])
                        ) {
                            // Creation dans la table RelationShips
                            $oneRuleRelationShip = new RuleRelationShip();
                            $oneRuleRelationShip->setRule($oneRule->getId());
                            $oneRuleRelationShip->setFieldNameSource($rel['source']);
                            $oneRuleRelationShip->setFieldNameTarget($rel['target']);
                            $oneRuleRelationShip->setFieldId($rel['rule']);
                            $oneRuleRelationShip->setParent($rel['parent']);
                            $oneRuleRelationShip->setDeleted(0);
                            // We don't create the field target if the relatiobnship is a parent one
                            // We only use this field to search in the source application, not to send the data to the target application.
                            if (empty($rel['parent'])) {
                                $tabRelationShips['target'][] = $rel['target'];
                            }
                            $tabRelationShips['source'][] = $rel['source'];

                            $this->entityManager->persist($oneRuleRelationShip);
                            $this->entityManager->flush();
                        }
                    }
                }

                //------------------------------- RuleFilter ------------------------

                if (!empty($request->request->get('filter'))) {
                    foreach ($request->request->get('filter') as $filter) {
                        $oneRuleFilter = new RuleFilter();
                        $oneRuleFilter->setTarget($filter['target']);
                        $oneRuleFilter->setRule($oneRule->getId());
                        $oneRuleFilter->setType($filter['filter']);
                        $oneRuleFilter->setValue($filter['value']);
                        $this->entityManager->persist($oneRuleFilter);
                        $this->entityManager->flush();
                    }
                }

                // --------------------------------------------------------------------------------------------------
                // Order all rules
                $this->ruleManager->orderRules();

                // --------------------------------------------------------------------------------------------------
                // Create rule history in order to follow all modifications
                // Encode every rule parameters
                $ruledata = json_encode(
                    [
                        'ruleName' => $nameRule,
                        'datereference' => $date_reference,
                        'content' => $tab_new_rule,
                        'filters' => $request->request->get('filter'),
                        'relationships' => $relationshipsBeforeSave,
                    ]
                );
                // Save the rule audit
                $oneRuleAudit = new RuleAudit();
                $oneRuleAudit->setRule($oneRule->getId());
                $oneRuleAudit->setDateCreated(new \DateTime());
                $oneRuleAudit->setData($ruledata);
                $this->entityManager->persist($oneRuleAudit);
                $this->entityManager->flush();

                // notification
                $solution_source = $this->solutionManager->get($this->sessionService->getParamRuleSourceSolution($ruleKey));
                $solution_source->setMessageCreateRule($this->sessionService->getParamRuleSourceModule($ruleKey));

                $solution_target = $this->solutionManager->get($this->sessionService->getParamRuleCibleSolution($ruleKey));
                $solution_target->setMessageCreateRule($this->sessionService->getParamRuleCibleModule($ruleKey));
                // notification

                // --------------------------------------------------------------------------------------------------

                // Détection règle root ou child rev 1.08 ----------------------
                // On réactualise les paramètres
                $tab_new_rule['content']['params'] = $p;
                $this->ruleManager->afterSave([
                    'ruleId' => $oneRule->getId(),
                    'ruleName' => $nameRule,
                    'oldRule' => ($this->sessionService->isParamRuleLastVersionIdEmpty($ruleKey)) ? '' : $this->sessionService->getParamRuleLastId($ruleKey),
                    'datereference' => $date_reference,
                    'connector' => $this->sessionService->getParamParentRule($ruleKey, 'connector'),
                    'content' => $tab_new_rule,
                    'relationships' => $relationshipsBeforeSave,
                    'module' => [
                        'source' => [
                            'solution' => $this->sessionService->getParamRuleSourceSolution($ruleKey),
                            'name' => $this->sessionService->getParamRuleSourceModule($ruleKey),
                        ],
                        'target' => [
                            'solution' => $this->sessionService->getParamRuleCibleSolution($ruleKey),
                            'name' => $this->sessionService->getParamRuleCibleModule($ruleKey),
                        ],
                    ],
                ]
                );
                if ($this->sessionService->isParamRuleExist($ruleKey)) {
                    $this->sessionService->removeParamRule($ruleKey);
                }
                $this->entityManager->getConnection()->commit();
                $response = 1;
            } catch (Exception $e) {
                $this->entityManager->getConnection()->rollBack();
                $this->logger->error('2;'.htmlentities($e->getMessage().' ('.$e->getFile().' line '.$e->getLine().')'));
                $response = '2;'.htmlentities($e->getMessage().' ('.$e->getFile().' line '.$e->getLine().')');
            }

            $this->entityManager->close();

            return new JsonResponse($response);
        }

        /**
         * TABLEAU DE BORD.
         *
         * @return Response
         *
         * @Route("/panel", name="regle_panel")
         */
        public function panelAction()
        {
            $language = $this->locale;
            $myddleware_support = $this->params->get('myddleware_support');

            $this->getInstanceBdd();
            $solution = $this->entityManager->getRepository(Solution::class)
                ->solutionActive();
            $lstArray = [];
            if ($solution) {
                foreach ($solution as $s) {
                    $lstArray[] = $s->getName();
                }
            }

            /** @var User $user */
            $user = $this->getUser();
            $nbFlux = 0;
            $listFlux = $this->documentRepository->countTypeDoc($user);
            foreach ($listFlux as $field => $value) {
                $nbFlux = $nbFlux + (int) $value['nb'];
            }

            return $this->render('Home/index.html.twig', [
                'errorByRule' => $this->ruleRepository->errorByRule($user),
                'listJobDetail' => $this->jobRepository->listJobDetail(),
                'nbFlux' => $nbFlux,
                'solutions' => $lstArray,
                'locale' => $language,
            ]
            );
        }

        /**
         * @return Response
         *
         * @Route("/graph/type/error/doc", name="graph_type_error_doc", options={"expose"=true})
         */
        public function graphTypeErrorAction()
        {
            $countTypeDoc = [];
            $documents = $this->documentRepository->countTypeDoc($this->getUser());
            if (count($documents)) {
                $countTypeDoc[] = ['test', 'test2'];
                foreach ($documents as $value) {
                    $countTypeDoc[] = [$value['global_status'], (int) $value['nb']];
                }
            }

            return $this->json($countTypeDoc);
        }

        /**
         * @return Response
         *
         * @Route("/graph/type/transfer/rule", name="graph_type_transfer_rule", options={"expose"=true})
         */
        public function graphTransferRuleAction()
        {
            $countTransferRule = [];
            $values = $this->documentRepository->countTransferRule($this->getUser());
            if (count($values)) {
                $countTransferRule[] = ['test', 'test2'];
                foreach ($values as $value) {
                    $countTransferRule[] = [$value['name'], (int) $value['nb']];
                }
            }

            return $this->json($countTransferRule);
        }

        /**
         * @return Response
         *
         * @Route("/graph/type/transfer/histo", name="graph_type_transfer_histo", options={"expose"=true})
         */
        public function graphTransferHistoAction()
        {
            $countTransferRule = [];
            $values = $this->home->countTransferHisto($this->getUser());
            if (count($values)) {
                $countTransferRule[] = [
                    'date',
                    $this->translator->trans('flux.gbl_status.open'),
                    $this->translator->trans('flux.gbl_status.error'),
                    $this->translator->trans('flux.gbl_status.cancel'),
                    $this->translator->trans('flux.gbl_status.close'),
                ];
                foreach ($values as $field => $value) {
                    $countTransferRule[] = [
                        $value['date'],
                        (int) $value['open'],
                        (int) $value['error'],
                        (int) $value['cancel'],
                        (int) $value['close'],
                    ];
                }
            }

            return $this->json($countTransferRule);
        }

        /**
         * @return Response
         *
         * @Route("/graph/type/job/histo", name="graph_type_job_histo", options={"expose"=true})
         */
        public function graphJobHistoAction()
        {
            $countTransferRule = [];
            $jobs = $this->jobRepository->findBy([], ['begin' => 'ASC'], 5);
            if (count($jobs)) {
                $countTransferRule[] = [
                    'date',
                    $this->translator->trans('flux.gbl_status.open'),
                    $this->translator->trans('flux.gbl_status.error'),
                    $this->translator->trans('flux.gbl_status.cancel'),
                    $this->translator->trans('flux.gbl_status.close'),
                ];
                foreach ($jobs as $job) {
                    $countTransferRule[] = [
                        $job->getBegin()->format('d/m/Y H:i:s'),
                        (int) $job->getOpen(),
                        (int) $job->getError(),
                        (int) $job->getCancel(),
                        (int) $job->getClose(),
                    ];
                }
            }

            return $this->json($countTransferRule);
        }

        /**
         * ANIMATION
         * No more submodule in Myddleware. We return a response 0 for the js (animation.js.
         *
         * @return Response
         *
         * @Route("/submodules", name="regle_submodules", methods={"POST"})
         */
        public function listSubModulesAction()
        {
            return new Response(0);
        }

        /**
         * VALIDATION DE L'ANIMATION.
         *
         * @return Response
         *
         * @Route("/validation", name="regle_validation_animation")
         */
        public function validationAnimationAction(Request $request)
        {
            $key = $this->sessionService->getParamRuleLastKey();

            try {
                $choiceSelect = $request->get('choice_select', null);
                if (null != $choiceSelect) {
                    if ('module' == $choiceSelect) {
                        // si le nom de la règle est inferieur à 3 caractères :
                        if (empty($this->sessionService->getParamRuleSourceSolution($key)) || strlen($this->sessionService->getParamRuleName($key)) < 3) {
                            $this->sessionService->setParamRuleNameValid($key, false);
                        } else {
                            $this->sessionService->setParamRuleNameValid($key, true);
                        }
                        $this->sessionService->setParamRuleSourceModule($key, $request->get('module_source'));
                        $this->sessionService->setParamRuleCibleModule($key, $request->get('module_target'));

                        return new Response('module');
                    } elseif ('template' == $choiceSelect) {
                        // Rule creation with the template selected in parameter
                        $ruleName = $request->get('name');
                        $templateName = $request->get('template');

                        $connectorSourceId = (int) $this->sessionService->getParamRuleConnectorSourceId($key);
                        $connectorTargetId = (int) $this->sessionService->getParamRuleConnectorCibleId($key);
                        /** @var User $user */
                        $user = $this->getUser();

                        $this->template->convertTemplate($ruleName, $templateName, $connectorSourceId, $connectorTargetId, $user);
                        // We return to the list of rule even in case of error (session messages will be displyed in the UI)/: See animation.js function animConfirm
                        return new Response('template');
                    }

                    return new Response(0);
                }

                return new Response(0);
            } catch (Exception $e) {
                return new Response($e->getMessage());
            }
        }

        /**
         * LISTE DES TEMPLATES.
         *
         * @return Response
         *
         * @Route("/list/template", name="regle_template")
         */
        public function listTemplateAction()
        {
            $key = $this->sessionService->getParamRuleLastKey();
            $solutionSourceName = $this->sessionService->getParamRuleSourceSolution($key);
            $solutionTargetName = $this->sessionService->getParamRuleCibleSolution($key);
            $templates = $this->template->getTemplates($solutionSourceName, $solutionTargetName);
            if (!empty($templates)) {
                $rows = '';
                foreach ($templates as $t) {
                    $rows .= '<tr>
                            <td><span data-id="'.$t['name'].'" class="glyphicon glyphicon-th-list"></span></td>
                            <td>'.$t['name'].'</td>
                            <td>'.$t['description'].'</td>
                         </tr>';
                }

                return new Response('<table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>'.$this->translator->trans('animate.choice.name').'</th>
                        <th>'.$this->translator->trans('animate.choice.description').'</th>
                    </tr>
                </thead>
                <tbody>
				'.$rows.'
                </tbody>
            </table>');
            }

            return new Response('<div class="alert alert-warning" role="alert"><span class="glyphicon glyphicon-warning-sign"></span> '.$this->translator->trans('animate.choice.empty').'</div>');
        }

        /**
         * CREATION - STEP ONE - ANIMATION.
         *
         * @return Response
         *
         * @Route("/create", name="regle_stepone_animation")
         */
        public function ruleStepOneAnimationAction()
        {
            if ($this->sessionService->isConnectorExist()) {
                $this->sessionService->removeMyddlewareConnector();
            }

            // New Rule
            $this->sessionService->setParamRuleLastKey(0);

            $key = $this->sessionService->getParamRuleLastKey();

            // Détecte s'il existe des erreurs
            if ($this->sessionService->isErrorNotEmpty($key, SessionService::ERROR_CREATE_RULE_INDEX)) {
                $error = $this->sessionService->getCreateRuleError($key);
                $this->sessionService->removeError($key, SessionService::ERROR_CREATE_RULE_INDEX);
            } else {
                $error = false;
            }

            // Liste source : solution avec au moins 1 connecteur
            $this->getInstanceBdd();

            $solutionSource = $this->entityManager->getRepository(Solution::class)
                ->solutionConnector('source', $this->getUser()->isAdmin(), $this->getUser()->getId());

            if (!empty($solutionSource)) {
                foreach ($solutionSource as $s) {
                    $source[] = $s->getName();
                }
                $this->sessionService->setParamConnectorSolutionSource($key, $source);
            }

            // Liste target : solution avec au moins 1 connecteur
            $solutionTarget = $this->entityManager->getRepository(Solution::class)
                ->solutionConnector('target', $this->getUser()->isAdmin(), $this->getUser()->getId());

            if (!empty($solutionTarget)) {
                foreach ($solutionTarget as $t) {
                    $target[] = $t->getName();
                }
                $this->sessionService->setParamConnectorSolutionTarget($key, $target);
            }

            return $this->render('Rule/create/step1simply.html.twig', [
                'source' => $solutionSource,
                'target' => $solutionTarget,
                'error' => $error,
            ]
            );
        }

        /**
         * LISTE DES MODULES POUR ANIMATION.
         *
         * @return Response
         *
         * @Route("/list/module", name="regle_list_module")
         */
        public function ruleListModuleAction(Request $request)
        {
            try {
                $id_connector = $request->get('id');
                $type = $request->get('type');
                $key = $this->sessionService->getParamRuleLastKey(); // It's a new rule, last key = 0

                // Control the request
                if (!in_array($type, ['source', 'cible']) || !is_numeric($id_connector)) {
                    throw $this->createAccessDeniedException();
                }
                $id_connector = (int) $id_connector;

                $this->getInstanceBdd();
                $connector = $this->entityManager->getRepository(Connector::class)
                    ->findById($id_connector); // infos connector

                $connectorParams = $this->entityManager->getRepository(ConnectorParam::class)
                    ->findByConnector($id_connector);    // infos params connector

                foreach ($connectorParams as $p) {
                    $this->sessionService->setParamRuleParentName($key, $type, $p->getName(), $p->getValue()); // params connector
                }
                $this->sessionService->setParamRuleConnectorParent($key, $type, $id_connector); // id connector
                $this->sessionService->setParamRuleParentName($key, $type, 'solution', $connector[0]->getSolution()->getName()); // nom de la solution

                $solution = $this->solutionManager->get($this->sessionService->getParamRuleParentName($key, $type, 'solution'));

                $params_connexion = $this->decrypt_params($this->sessionService->getParamParentRule($key, $type));
                $params_connexion['idConnector'] = $id_connector;

                $solution->login($params_connexion);

                $t = (('source' == $type) ? 'source' : 'target');

                $liste_modules = tools::composeListHtml($solution->get_modules($t), $this->translator->trans('create_rule.step1.choose_module'));

                return new Response($liste_modules);
            } catch (Exception $e) {
                return new Response('<option value="">Aucun module pour ce connecteur</option>');
            }
        }

        /* ******************************************************
         * METHODES PRATIQUES
         ****************************************************** */

        // CREATION REGLE - STEP ONE : Liste des connecteurs pour un user
        private function liste_connectorAction($type)
        {
            $this->getInstanceBdd();
            $solution = $this->entityManager->getRepository(Connector::class)->findAllConnectorByUser($this->getUser()->getId(), $type); // type = source ou target
            $lstArray = [];
            if ($solution) {
                foreach ($solution as $s) {
                    $lstArray[$s['name'].'_'.$s['id_connector']] = ucfirst($s['label']);
                }
            }

            $lst_solution = tools::composeListHtml($lstArray, $this->translator->trans('create_rule.step1.list_empty'));

            return $lst_solution;
        }

        // CREATION REGLE - STEP THREE - Retourne les paramètres dans un bon format de tableau
        private function createListeParamsRule($fields, $formula, $params)
        {
            $phrase_placeholder = $this->translator->trans('rule.step3.placeholder');
            $tab = [];

            // FIELDS ------------------------------------------
            if ($fields) {
                $champs = explode(';', $fields);
                foreach ($champs as $champ) {
                    $chp = explode('[=]', $champ);

                    if ($chp[0]) {
                        if ($phrase_placeholder != $chp[1] && 'my_value' != $chp[1]) {
                            $tab['fields']['name'][$chp[0]]['champs'][] = $chp[1];
                        }
                    }
                }
            }

            // FORMULA -----------------------------------------
            if ($formula) {
                $formules = explode(';', $formula);

                foreach ($formules as $formule) {
                    $chp = explode('[=]', $formule);
                    if ($chp[0]) {
                        if (!empty($chp[1])) {
                            $tab['fields']['name'][$chp[0]]['formule'][] = $chp[1];
                        }
                    }
                }
            }

            // PARAMS -----------------------------------------
            if ($params) {
                foreach ($params as $k => $p) {
                    $tab['params'][$p['name']] = $p['value'];
                }
            }

            return $tab;
        }

        // Crée la pagination avec le Bundle Pagerfanta en fonction d'une requete
        private function nav_pagination($params, $orm = true)
        {
            /*
             * adapter_em_repository = requete
             * maxPerPage = integer
             * page = page en cours
             */

            if (is_array($params)) {
                /* DOC :
                 * $pager->setCurrentPage($page);
                    $pager->getNbResults();
                    $pager->getMaxPerPage();
                    $pager->getNbPages();
                    $pager->haveToPaginate();
                    $pager->hasPreviousPage();
                    $pager->getPreviousPage();
                    $pager->hasNextPage();
                    $pager->getNextPage();
                    $pager->getCurrentPageResults();
                */

                $compact = [];

                //On passe l’adapter au bundle qui va s’occuper de la pagination
                if ($orm) {
                    $compact['pager'] = new Pagerfanta(new DoctrineORMAdapter($params['adapter_em_repository']));
                } else {
                    $compact['pager'] = new Pagerfanta(new ArrayAdapter($params['adapter_em_repository']));
                }

                //On définit le nombre d’article à afficher par page (que l’on a biensur définit dans le fichier param)
                $compact['pager']->setMaxPerPage($params['maxPerPage']);
                try {
                    $compact['entities'] = $compact['pager']
                        //On indique au pager quelle page on veut
                        ->setCurrentPage($params['page'])
                        //On récupère les résultats correspondant
                        ->getCurrentPageResults();

                    $compact['nb'] = $compact['pager']->getNbResults();
                } catch (\Pagerfanta\Exception\NotValidCurrentPageException $e) {
                    //Si jamais la page n’existe pas on léve une 404
                    throw $this->createNotFoundException("Cette page n'existe pas.");
                }

                return $compact;
            }

            return false;
        }

        // Décrypte les paramètres de connexion d'une solution
        private function decrypt_params($tab_params)
        {
            // Instanciate object to decrypte data
            $encrypter = new Encrypter(substr($this->params->get('secret'), -16));
            if (is_array($tab_params)) {
                $return_params = [];
                foreach ($tab_params as $key => $value) {
                    if (
                        is_string($value)
                        && !in_array($key, ['solution', 'module']) // Soe data aren't crypted
                    ) {
                        $return_params[$key] = $encrypter->decrypt($value);
                    }
                }

                return $return_params;
            }

            return $encrypter->decrypt($tab_params);
        }
    }
}
