<?php

namespace App\Controller;

use App\Form\DatabaseSetupType;
use App\Entity\DatabaseParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DatabaseSetupController extends AbstractController
{
    /**
     * @Route("install/database/setup", name="database_setup")
     */
    public function index(Request $request, KernelInterface $kernel): Response
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
   
        if ($form->isSubmitted() && $form->isValid()){
    
            $envLocal = __DIR__.'/../../.env.local';
            if(file_exists($envLocal) && is_file($envLocal)){
                // we edit the database connection parameters with form input
                $newUrl = 'DATABASE_URL="mysql://'.$database->getUser().':'.$database->getPassword().'@'.$database->getHost().':'.$database->getPort().'/'.$database->getName().'?serverVersion=5.7"';
                // write new URL into the .env.local file (EOL ensures it's written on a new line)
                file_put_contents($envLocal, PHP_EOL.$newUrl, LOCK_EX);
                // TODO : how can I ensure that there isn't already a DATABASE_URL line in the file ?
            }
       
        }

        return $this->render('database_setup/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
