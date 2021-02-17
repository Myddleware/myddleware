<?php

namespace App\Controller;

use App\Entity\DatabaseParameter;
use App\Form\DatabaseSetupType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DatabaseSetupController extends AbstractController
{
    /**
     * @Route("install/database/setup", name="database_setup")
     */
    public function index(Request $request): Response
    {

          //get all parameters from config/parameters.yml and push them in a new instance of DatabaseParameters()
          $database = new DatabaseParameter();
          $database->setDriver($this->getParameter('database_driver'));
          $database->setHost($this->getParameter('database_host'));
          $database->setPort($this->getParameter('database_port'));
          $database->setName($this->getParameter('database_name'));
          $database->setUser($this->getParameter('database_user'));
          $database->setSecret($this->getParameter('secret'));

        $form = $this->createForm(DatabaseSetupType::class, $database);
        $form->handleRequest($request);
   
        return $this->render('database_setup/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
