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

namespace App\Controller;

use App\Entity\Config;
use App\Entity\Connector;
use App\Entity\Rule;
use App\Entity\Solution;
use App\Form\ConnectorType;
use App\Manager\permission;
use App\Manager\SolutionManager;
use App\Manager\ToolsManager;
use App\Repository\RuleRepository;
use App\Service\SessionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/rule")
 */
class ConnectorController extends AbstractController
{
    protected $params;
    private SessionService $sessionService;
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private SolutionManager $solutionManager;
    private LoggerInterface $logger;

    public function __construct(
        SolutionManager $solutionManager,
        SessionService $sessionService,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->solutionManager = $solutionManager;
        $this->sessionService = $sessionService;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        // Init parameters
        $configRepository = $this->entityManager->getRepository(Config::class);
        $configs = $configRepository->findAll();
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->params[$config->getName()] = $config->getvalue();
            }
        }
    }

    /**
     * CALLBACK POUR LES APIS.
     *
     * @Route("/connector/callback/", name="connector_callback", options={"expose"=true})
     */
    public function callBack()
    {
        try {
            // Nom de la solution
            if (!$this->sessionService->isSolutionNameExist()) {
                return new Response('');
            }

            $solution_name = $this->sessionService->getSolutionName();
            $solution = $this->solutionManager->get($solution_name);

            // ETAPE 2 : Récupération du retour de la Popup en GET et génération du token final
            if (isset($_GET[$solution->nameFieldGet])) {
                $connectorSource = $this->sessionService->getParamConnectorSource();

                $solution->init($connectorSource); // Affecte les variables

                $solution->setAuthenticate($_GET[$solution->nameFieldGet]);

                if ($solution->refresh_token) { // Si RefreshToken
                    $this->sessionService->setParamConnectorSourceRefreshToken($solution->getRefreshToken());
                }

                $solution->login($connectorSource);

                // Sauvegarde des 2 jetons en session afin de les enregistrer dans les paramètres du connecteur
                $this->sessionService->setParamConnectorSourceToken($solution->getAccessToken());

                if ($solution->refresh_token) { // Si RefreshToken
                    $this->sessionService->setParamConnectorSourceRefreshToken($solution->getRefreshToken());
                }

                return $this->redirect($this->generateUrl('connector_callback'));
            }

            // SOLUTION AVEC POPUP ---------------------------------------------------------------------
            // ATAPE 1 si la solution utilise un callback et le js
            if ($solution->callback && $solution->js) {
                if (!$this->sessionService->isParamConnectorSourceExist()) {
                    $params_connexion_solution = $this->sessionService->getParamConnectorSource();
                }
                if (!$this->sessionService->isParamConnectorSourceTokenExist()) {
                    $params_connexion_solution['token'] = $this->sessionService->getParamConnectorSourceToken();
                }
                if (!$this->sessionService->isParamConnectorSourceRefreshTokenExist()) {
                    $params_connexion_solution['refreshToken'] = $this->sessionService->getParamConnectorSourceRefreshToken();
                }

                $solution->init($params_connexion_solution); // Affecte les variables

                $error = $solution->login($params_connexion_solution);

                // Gestion des erreurs retour méthode login
                if (!empty($error)) {
                    return new Response('');
                }

                // Autorisation de l'application
                if (!empty($_POST['solutionjs'])) {
                    // Déclenche la pop up
                    if (!empty($_POST['detectjs'])) {
                        $callbackUrl = $solution->getCreateAuthUrl((isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$this->generateUrl('connector_callback'));
                        if (!$this->sessionService->isParamConnectorSourceTokenExist()) {
                            $solution->setAccessToken($this->sessionService->getParamConnectorSourceToken());
                        }
                        // Redirection vers une autorisation manuel
                        else {
                            return new Response($solution->js.';'.urldecode($callbackUrl)); // Url de l'authentification prêt à être ouvert en popup
                        }

                        // 1er test de validité du Token
                        $testToken = $solution->testToken();

                        if (!empty($testToken['error']['code'])) {
                            if (401 == $testToken['error']['code'] || 404 == $testToken['error']['code']) {
                                $this->sessionService->setParamConnectorSourceToken(null);
                                $url = $solution->getCreateAuthUrl($callbackUrl);

                                return new Response($solution->js.';'.urldecode($url)); // Url de l'authentification prêt à être ouvert en popup
                            }
                        }

                        return new Response($solution->js.';'.$callbackUrl);	// tentative de connexion
                    } // detect js

                    if ($this->sessionService->isParamConnectorSourceTokenExist()) {
                        $solution->setAccessToken($this->sessionService->getParamConnectorSourceToken());
                    }
                    // 2nd Test la validité du token
                    $testToken = $solution->testToken();

                    // Erreur sans ouvrir la popup
                    if (404 == $testToken['error']['code'] || 0 === $testToken['error']['code']) {
                        return new Response('2;'.$testToken['error']['message']); // Error Not Found
                    }

                    if (isset($testToken['error']['code']) && !empty($testToken['error']['code']) && !empty($testToken['error']['message'])) {
                        return new Response($testToken['error']['code'].';'.$testToken['error']['message']);
                    }

                    if ($this->sessionService->isParamConnectorSourceTokenExist()) {
                        if (isset($testToken['error']['message']) && !empty($testToken['error']['message'])) {
                            return new Response($testToken['error']['message'].';'); // Erreur de connexion
                        }

                        $solution->connexion_valide = true;

                        return new Response(1); // Connexion réussi
                    }
                }

                return new Response('<script type="text/javascript" language="javascript">window.close();</script>'); // Ferme la popup automatiquement
            } // fin
            // SOLUTION AVEC POPUP ---------------------------------------------------------------------

                throw new Exception('Failed load class');
        } catch (Exception $e) {
            return new Response($e->getMessage());
        }

        return new Response('');
    }

    /**
     * Contrôle si le fichier upload est valide puis le déplace.
     *
     * @Route("/connector/upload/{solution}", name="upload", options={"expose"=true})
     */
    public function upload($solution): Response
    {
        if (isset($solution)) {
            $output_dir = __DIR__.'/../Custom/Solutions/'.trim($solution).'/file/';
            // Get canonicalized absolute pathname
            $path = realpath($output_dir);
            // If it exist, check if it's a directory
            if (false === $path || !is_dir($path)) {
                try {
                    if (!mkdir($output_dir, 755, true)) {
                        echo '0;'.'Directory '.$output_dir.' doesn\'t exist. Failed to create this directory. Please check directory Custom is readable by webuser. You can create manually the directory for the Sage wsdl too. ';
                        exit;
                    }
                } catch (Exception $e) {
                    echo '0;'.$e->getMessage().'. Please check you have the web user has the permission to write in the directory '.__DIR__.'/../Custom . ';
                    exit;
                }
            }
        }

        // Supprime ancien fichier de config s'il existe
        if (isset($_GET['file']) && '' != $_GET['file']) {
            $name_without_space = str_replace(' ', '_', $_GET['file']);
            $path_delete_old = $output_dir.$name_without_space;
            if (file_exists($path_delete_old)) {
                unlink($path_delete_old);
                echo '<br/><br/><p><span class="label label-warning">'.$this->translator->trans('create_connector.upload_delete').' : '.htmlentities($name_without_space).'</span></p>';
            }
        }

        if ('all' == $solution) {
            if ($this->sessionService->isUploadNameExist()) {
                echo '1;'.$this->sessionService->getUploadName();
                $this->sessionService->removeUpload();
                exit;
            }

            if ($this->sessionService->isUploadErrorExist()) {
                echo '0;'.$this->sessionService->getUploadError();
                $this->sessionService->removeUpload();
                exit;
            }
        }

        if (isset($_FILES['myfile']) && isset($output_dir) && is_dir($output_dir)) {
            if ($_FILES['myfile']['error'] > 0) {
                $error = $_FILES['file']['error'];
                echo '0;'.$error;
                $this->sessionService->setUploadError($error);
            } else {
                // A list of permitted file extensions
                $configRepository = $this->getDoctrine()->getManager()->getRepository(Config::class);
                $extensionAllowed = $configRepository->findOneBy(['name' => 'extension_allowed']);
                if (!empty($extensionAllowed)) {
                    $allowedJson = $extensionAllowed->getValue();
                    if (!empty($allowedJson)) {
                        $allowed = json_decode($allowedJson);
                    }
                }
                $extension = pathinfo($_FILES['myfile']['name'], PATHINFO_EXTENSION);

                if (!in_array(strtolower($extension), $allowed)) {
                    echo '0;'.$this->translator->trans('create_connector.upload_error_ext');
                    exit;
                }

                $name_without_space = str_replace(' ', '_', $_FILES['myfile']['name']);
                $new_name = time().'_'.$name_without_space;

                if (move_uploaded_file($_FILES['myfile']['tmp_name'], $output_dir.$new_name)) {
                    echo '1;'.$this->translator->trans('create_connector.upload_success').' : '.$new_name;
                    $this->sessionService->setUploadName($new_name);
                    exit;
                }

                echo '0;'.$this->translator->trans('create_connector.upload_error');
                exit;

                exit;
            }
        } else {
            return $this->render('Connector/upload.html.twig', ['solution' => $solution]
            );
        }
    }

    /**
     * CREATION D UN CONNECTEUR LISTE.
     *
     * @Route("/connector/create", name="regle_connector_create")
     */
    public function create(): Response
    {
        $solution = $this->entityManager->getRepository(Solution::class)->solutionActive();
        $lstArray = [];
        if ($solution) {
            foreach ($solution as $s) {
                $lstArray[$s->getName()] = ucfirst($s->getName());
            }
        }

        $lst_solution = ToolsManager::composeListHtml($lstArray, $this->translator->trans('create_rule.step1.list_empty'));
        $this->sessionService->setConnectorAnimation(false);
        $this->sessionService->setConnectorAddMessage('list');

        return $this->render('Connector/index.html.twig', [
            'solutions' => $lst_solution, ]
        );
    }

    /**
     * CREATION D'UN CONNECTEUR.
     *
     * @return RedirectResponse|Response
     *
     * @Route("/connector/insert", name="regle_connector_insert")
     *
     * @throws Exception
     */
    public function connectorInsert(Request $request)
    {
        $type = '';

        $solution = $this->getDoctrine()
                            ->getManager()
                            ->getRepository(Solution::class)
                            ->findOneBy(['name' => $this->sessionService->getParamConnectorSourceSolution()]);

        $connector = new Connector();
        $connector->setSolution($solution);

        if (null != $connector->getSolution()) {
            $fieldsLogin = $this->solutionManager->get($connector->getSolution()->getName())->getFieldsLogin();
        } else {
            $fieldsLogin = [];
        }

        $form = $this->createForm(ConnectorType::class, $connector, [
            'method' => 'PUT',
            'attr' => ['fieldsLogin' => $fieldsLogin, 'secret' => $this->getParameter('secret')],
        ]);

        if ('POST' == $request->getMethod() && $this->sessionService->isParamConnectorExist()) {
            try {
                $form->handleRequest($request);
                $form->submit($request->request->get($form->getName()));
                if ($form->isValid()) {
                    $solution = $connector->getSolution();
                    $multi = $solution->getSource() + $solution->getTarget();
                    if ($this->sessionService->getConnectorAnimation()) {
                        // animation add connector
                        $type = $this->sessionService->getParamConnectorAddType();
                        // si la solution ajouté n'existe pas dans la page en cours on va la rajouter manuellement
                        $solution = $this->sessionService->getParamConnectorSourceSolution();
                        if (!in_array($solution, json_decode($this->sessionService->getSolutionType($type)))) {
                            $this->sessionService->setParamConnectorValues($type.';'.$solution.';'.$multi.';'.$solution->getId());
                        }
                    }

                    $connectorParams = $connector->getConnectorParams();
                    $connector->setConnectorParams(null);
                    $connector->setNameSlug($connector->getName());
                    $connector->setDateCreated(new \DateTime());
                    $connector->setDateModified(new \DateTime());
                    $connector->setCreatedBy($this->getUser()->getId());
                    $connector->setModifiedBy($this->getUser()->getId());
                    $connector->setDeleted(0);

                    $this->entityManager->persist($connector);
                    $this->entityManager->flush();

                    foreach ($connectorParams as $key => $cp) {
                        $cp->setConnector($connector);
                        $this->entityManager->persist($cp);
                        $this->entityManager->flush();
                    }

                    $this->sessionService->removeConnector();
                    if (
                            !empty($this->sessionService->getConnectorAddMessage())
                        && 'list' == $this->sessionService->getConnectorAddMessage()
                    ) {
                        $this->sessionService->removeConnectorAdd();

                        return $this->redirect($this->generateUrl('regle_connector_list'));
                    }
                    // animation
                    $message = '';
                    if (!empty($this->sessionService->getConnectorAddMessage())) {
                        $message = $this->sessionService->getConnectorAddMessage();
                    }
                    $this->sessionService->removeConnectorAdd();

                    return $this->render('Connector/createout_valid.html.twig', [
                        'message' => $message,
                        'type' => $type,
                    ]
                        );
                }
                dump($form);
                exit();

                return $this->redirect($this->generateUrl('regle_connector_list'));
                //-----------
            } catch (Exception $e) {
                $this->logger->error('Error : '.$e->getMessage().' File :  '.$e->getFile().' Line : '.$e->getLine());
                throw $this->createNotFoundException('Error : '.$e->getMessage().' File :  '.$e->getFile().' Line : '.$e->getLine());
            }
        } else {
            $this->logger->error('Error : '.$e->getMessage().' File :  '.$e->getFile().' Line : '.$e->getLine());
            throw $this->createNotFoundException('Error');
        }
    }

    /**
     * LISTE DES CONNECTEURS.
     *
     * @Route("/connector/list", name="regle_connector_list", defaults={"page"=1})
     * @Route("/connector/list/page-{page}", name="regle_connector_page", requirements={"page"="\d+"})
     */
    public function connectorList($page = 1): Response
    {
        try {
            // ---------------
            $compact['nb'] = 0;

            $compact = $this->nav_pagination([
                'adapter_em_repository' => $this->entityManager->getRepository(Connector::class)
                                            ->findListConnectorByUser($this->getUser()->isAdmin(), $this->getUser()->getId()),
                'maxPerPage' => $this->params['pager'] ?? 20,
                'page' => $page,
            ]);

            // Si tout se passe bien dans la pagination
            if ($compact) {
                // Si aucun connecteur
                if ($compact['nb'] < 1 && !intval($compact['nb'])) {
                    $compact['entities'] = '';
                    $compact['pager'] = '';
                }

                return $this->render('Connector/list.html.twig', [
                    'nb' => $compact['nb'],
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
     * SUPPRESSION DU CONNECTEUR.
     *
     * @Route("/connector/delete/{id}", name="connector_delete")
     */
    public function connectorDelete(Request $request, $id): RedirectResponse
    {
        $session = $request->getSession();
        if (isset($id)) {
            // Check permission
            if ($this->getUser()->isAdmin()) {
                $list_fields_sql = ['id' => $id];
            } else {
                $list_fields_sql =
                    ['id' => $id,
                        'createdBy' => $this->getUser()->getId(),
                    ];
            }

            // Get the connector using its id
            $connector = $this->getDoctrine()
                        ->getManager()
                        ->getRepository(Connector::class)
                        ->findOneBy($list_fields_sql);

            if (null === $connector) {
                return $this->redirect($this->generateUrl('regle_connector_list'));
            }
            try {
                /** @var RuleRepository $ruleRepository */
                $ruleRepository = $this->getDoctrine()->getManager()->getRepository(Rule::class);
                // Check if a rule uses this connector (source and target)
                $rule = $ruleRepository->findOneBy([
                    'connectorTarget' => $connector,
                    'deleted' => 0,
                ]);
                if (empty($rule)) {
                    $rule = $ruleRepository->findOneBy([
                        'connectorSource' => $connector,
                        'deleted' => 0,
                    ]);
                }
                // Error message in case a rule using this connector exists
                if (!empty($rule)) {
                    $session->set('error', [$this->translator->trans('error.connector.remove_with_rule').' '.$rule->getName()]);
                } else {
                    // Flag the connector as deleted
                    $connector->setDeleted(1);
                    $this->getDoctrine()->getManager()->persist($connector);
                    $this->getDoctrine()->getManager()->flush();
                }
            } catch (\Doctrine\DBAL\DBALException $e) {
                $session->set('error', [$e->getPrevious()->getMessage()]);
            }

            return $this->redirect($this->generateUrl('regle_connector_list'));
        }
    }

    /**
     * FICHE D'UN CONNECTEUR.
     *
     * @Route("/connector/view/{id}", name="connector_open")
     *
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws NonUniqueResultException
     */
    public function connectorOpen(Request $request, $id)
    {
        $qb = $this->entityManager->getRepository(Connector::class)->createQueryBuilder('c');
        $qb->select('c', 'cp')->leftjoin('c.connectorParams', 'cp');

        if ($this->getUser()->isAdmin()) {
            $qb->where('c.id =:id AND c.deleted = 0')->setParameter('id', $id);
        } else {
            $qb->where('c.id =:id and c.createdBy =:createdBy AND c.deleted = 0')->setParameter(['id' => $id, 'createdBy' => $this->getUser()->getId()]);
        }
        // Detecte si la session est le support ---------
        // Infos du connecteur
        $connector = $qb->getQuery()->getOneOrNullResult();

        if (!$connector) {
            throw $this->createNotFoundException("This connector doesn't exist");
        }

        if ($this->getUser()->isAdmin()) {
            $qb->where('c.id =:id')->setParameter('id', $id);
        } else {
            $qb->where('c.id =:id and c.createdBy =:createdBy')->setParameter(['id' => $id, 'createdBy' => $this->getUser()->getId()]);
        }
        // Detecte si la session est le support ---------
        // Infos du connecteur
        $connector = $qb->getQuery()->getOneOrNullResult();

        if (!$connector) {
            throw $this->createNotFoundException("This connector doesn't exist");
        }

        // Create connector form
        // $form = $this->createForm(new ConnectorType($this->container), $connector, ['action' => $this->generateUrl('connector_open', ['id' => $id])]);

        if (null != $connector->getSolution()) {
            $fieldsLogin = $this->solutionManager->get($connector->getSolution()->getName())->getFieldsLogin();
        } else {
            $fieldsLogin = [];
        }

        $form = $this->createForm(ConnectorType::class, $connector, [
            'action' => $this->generateUrl('connector_open', ['id' => $id]),
            'method' => 'POST',
            'attr' => ['fieldsLogin' => $fieldsLogin, 'secret' => $this->getParameter('secret')],
        ]);

        // If the connector has been changed
        if ('POST' == $request->getMethod()) {
            try {
                $form->handleRequest($request);
                // SAVE
                $params = $connector->getConnectorParams();
                // SAVE PARAMS CONNECTEUR
                if (count($params) > 0) {
                    $this->entityManager->persist($connector);
                    $this->entityManager->flush();

                    return $this->redirect($this->generateUrl('regle_connector_list'));
                }

                return new Response(0);
            } catch (Exception $e) {
                return new Response($e->getMessage());
            }
        }
        // Display the connector
        else {
            return $this->render('Connector/edit/fiche.html.twig', [
                'connector' => $connector,
                'form' => $form->createView(),
                'connector_name' => $connector->getName(),
                ]
            );
        }
    }

    /* ******************************************************
     * ANIMATION
     ****************************************************** */

    /**
     * LISTE DES CONNECTEURS POUR ANIMATION.
     *
     * @Route("/connector/list/solution", name="regle_connector_by_solution")
     */
    public function connectorListSolution(Request $request): Response
    {
        $id = $request->get('id', null);

        if (null != $id) {
            if ($this->getUser()->isAdmin()) {
                $list_fields_sql = ['solution' => (int) $id,
                    'deleted' => 0,
                ];
            } else {
                $list_fields_sql =
                    ['solution' => (int) $id,
                        'deleted' => 0,
                        'createdBy' => $this->getUser()->getId(),
                    ];
            }
            $listConnector = $this->entityManager->getRepository(Connector::class)->findBy($list_fields_sql);
            $lstArray = [];
            foreach ($listConnector as $c) {
                $lstArray[$c->getId()] = ucfirst($c->getName());
            }
            $lst = ToolsManager::composeListHtml($lstArray, $this->translator->trans('create_rule.step1.choose_connector'));

            return new Response($lst);
        }

        return new Response('');
    }

    /**
     * CREATION D'UN CONNECTEUR LISTE animation.
     *
     * @Route("/connector/createout/{type}", name="regle_connector_create_out")
     */
    public function createOut($type): Response
    {
        $solution = $this->entityManager->getRepository(Solution::class)->solutionConnectorType($type);
        $lstArray = [];
        if ($solution) {
            foreach ($solution as $s) {
                $lstArray[$s['name']] = ucfirst($s['name']);
            }
        }

        $lst_solution = ToolsManager::composeListHtml($lstArray, $this->translator->trans('create_rule.step1.list_empty'));

        $this->sessionService->setConnectorAddMessage($this->translator->trans('create_rule.step1.connector'));
        $this->sessionService->setParamConnectorAddType(strip_tags($type));
        $this->sessionService->setConnectorAnimation(true);

        return $this->render('Connector/createout.html.twig', [
            'solutions' => $lst_solution,
        ]
        );
    }

    /**
     * RETOURNE LES INFOS POUR L AJOUT D UN CONNECTEUR EN JQUERY.
     *
     * @Route("/connector/insert/solution", name="regle_connector_insert_solution")
     */
    public function connectorInsertSolutionAction(): Response
    {
        if ($this->sessionService->isConnectorValuesExist()) {
            $values = $this->sessionService->getConnectorValues();
            $this->sessionService->removeConnectorValues();

            return new Response($values);
        }

        return new Response(0);
    }

    /* ******************************************************
     * METHODES PRATIQUES
     ****************************************************** */

    // Crée la pagination avec le Bundle Pagerfanta en fonction d'une requete
    private function nav_pagination($params, $orm = true)
    {
        /*
         * adapter_em_repository = requete
         * maxPerPage = integer
         * page = page en cours
         */

        if (is_array($params)) {
            $compact = [];
            //On passe l’adapter au bundle qui va s’occuper de la pagination
            if ($orm) {
                $queryBuilder = $params['adapter_em_repository'];
                $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));
                $compact['pager'] = $pagerfanta;
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
            } catch (NotValidCurrentPageException $e) {
                //Si jamais la page n’existe pas on léve une 404
                throw $this->createNotFoundException('Page not found. '.$e->getMessage());
            }

            return $compact;
        }

        return false;
    }

/**
 * @Route("/connector/{id}/detail", name="connector_detail")
 */
public function detailAction(int $id)
{
    $sensitiveFields = !empty($_ENV['SENSITIVE_FIELDS']) ? explode(',', $_ENV['SENSITIVE_FIELDS']) : [];
    
    $connector = $this->entityManager->getRepository(Connector::class)->find($id);

    if (!$connector) {
        throw $this->createNotFoundException('The connector does not exist');
    }

    $paramConnexion = [];
   
    foreach ($connector->getConnectorParams() as $param) {
        $paramConnexion[$param->getName()] = $param->getValue();
    }
    $encrypter = new \Illuminate\Encryption\Encrypter(substr($this->getParameter('secret'), -16));
    foreach ($paramConnexion as $key => $value) {
        if (is_string($value)) {
            try {
                $paramConnexion[$key] = $encrypter->decrypt($value);
            } catch (\Exception $e) {
              
            }
        }
    }

    // Passez les paramètres décryptés à la vue
    return $this->render('Connector/detail/detail.html.twig', [
        'connector' => $connector,
        'paramConnexion' => $paramConnexion, 
        'sensitiveFields' => $sensitiveFields,
    ]);
}
}
