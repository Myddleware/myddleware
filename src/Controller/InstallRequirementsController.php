<?php

namespace App\Controller;

use App\Repository\ConfigRepository;
use Doctrine\DBAL\ConnectionException as DoctrineConnectionException;
use Doctrine\DBAL\Driver\Exception as DBALException;
use Doctrine\DBAL\Driver\PDO\Exception as DoctrinePDOException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Exception;
use PDOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Requirements\SymfonyRequirements;

class InstallRequirementsController extends AbstractController
{
    private $symfonyRequirements;
    private $phpVersion;
    private $systemStatus;
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/install/requirements", name="install_requirements")
     */
    public function index(TranslatorInterface $translator): Response
    {
        try {
            //to help voter decide whether we allow access to install process again or not
            $configs = $this->configRepository->findAll();
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    if ('allow_install' === $config->getName()) {
                        $this->denyAccessUnlessGranted('DATABASE_VIEW', $config);
                    }
                }
            }
        } catch (Exception | DBALException | PDOException | DoctrinePDOException | TableNotFoundException $e) {

            // we start by checking if the root folder contains a .env.local file.
            // if it does, we do nothing. If it doesn't, we create an empty one.
            if (!file_exists(__DIR__ . '/../../.env.local')) {
                file_put_contents(__DIR__ . '/../../.env.local', '');
            }   

            // Get the file permissions in octal format
            $permissions = fileperms(__DIR__ . '/../../.env.local');

            // Convert the permissions to a human-readable format
            $envLocalFileRights = substr(sprintf('%o', $permissions), -4);

            // create a variable that indicate whether the .env.local file is writable
            $envLocalFileWritable = is_writable(__DIR__ . '/../../.env.local');

            // if we have a database in .env.local but the connection hasn't been made yet
            if ($e instanceof DBALException | $e instanceof PDOException | $e instanceof DoctrineConnectionException | $e instanceof TableNotFoundException) {
                $this->symfonyRequirements = new SymfonyRequirements();

                $this->phpVersion = phpversion();

                $checkPassed = true;

                $requirementsErrorMessages = [];
                foreach ($this->symfonyRequirements->getRequirements() as $req) {
                    if (!$req->isFulfilled()) {
                        $requirementsErrorMessages[] = $req->getHelpText();
                        $checkPassed = false;
                    }
                }

                $recommendationMessages = [];
                foreach ($this->symfonyRequirements->getRecommendations() as $req) {
                    if (!$req->isFulfilled()) {
                        $recommendationMessages[] = $req->getHelpText();
                    }
                }

                $this->systemStatus = '';
                if (!$checkPassed) {
                    $this->systemStatus = $translator->trans('install.system_status_not_ready');
                } else {
                    $this->systemStatus = $translator->trans('install.system_status_ready');
                }

                return $this->render('install_requirements/index.html.twig', [
                    'php_version' => $this->phpVersion,
                    'error_messages' => $requirementsErrorMessages,
                    'recommendation_messages' => $recommendationMessages,
                    'system_status' => $this->systemStatus,
                    'env_local_file_rights' => $envLocalFileRights,
                    'env_local_file_writable' => $envLocalFileWritable,
                ]);
            } else {

                // if we already have a database section in the .env.local file, deny access and add a flash message
                if (!empty($_ENV['DATABASE_HOST']) && !empty($_ENV['DATABASE_NAME'])) {
                    $this->addFlash('error_install', $translator->trans('install.database_already_exists'));
                    return $this->redirectToRoute('login');
                }

                // if other error, deny access
                return $this->redirectToRoute('login');
            }
        }
        $this->symfonyRequirements = new SymfonyRequirements();

        $this->phpVersion = phpversion();

        $checkPassed = true;

        $requirementsErrorMessages = [];
        foreach ($this->symfonyRequirements->getRequirements() as $req) {
            if (!$req->isFulfilled()) {
                $requirementsErrorMessages[] = $req->getHelpText();
                $checkPassed = false;
            }
        }

        $recommendationMessages = [];
        foreach ($this->symfonyRequirements->getRecommendations() as $req) {
            if (!$req->isFulfilled()) {
                $recommendationMessages[] = $req->getHelpText();
            }
        }

        $this->systemStatus = '';
        if (!$checkPassed) {
            $this->systemStatus = $translator->trans('install.system_status_not_ready');
        } else {
            $this->systemStatus = $translator->trans('install.system_status_ready');
        }

        //allow access if no errors
        return $this->render('install_requirements/index.html.twig', [
            'php_version' => $this->phpVersion,
            'error_messages' => $requirementsErrorMessages,
            'recommendation_messages' => $recommendationMessages,
            'system_status' => $this->systemStatus,
            'env_local_file_rights' => $envLocalFileRights,
            'env_local_file_writable' => $envLocalFileWritable,
        ]);
    }
}
