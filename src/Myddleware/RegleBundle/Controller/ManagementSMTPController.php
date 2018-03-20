<?php

namespace Myddleware\RegleBundle\Controller;

use Myddleware\RegleBundle\Form\managementSMTPType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;

class ManagementSMTPController extends Controller
{
 const PATH = './../app/config/parameters_smtp.yml';

    public function indexAction()
    {
        $form = $this->createCreateForm();
        $form = $this->getData($form);
        return $this->render('RegleBundle:ManagementSMTP:index.html.twig', array('form' => $form->createView()));
    }

    /**
     * Read config stmp
     *
     */
    public function createConfigAction(Request $request)
    {
        try {
            $form = $this->createCreateForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->setData($form);
                return $this->redirect($this->generateUrl('management_smtp_index'));
            }
        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }
        return $this->render('RegleBundle:ManagementSMTP:index.html.twig', array('form' => $form->createView()));
    }


    /**
     * Creates a form to create a JobScheduler entity.
     *
     * @param JobScheduler $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm()
    {
        $form = $this->createForm(new managementSMTPType(), null, array(
            'action' => $this->generateUrl('management_smtp_create'),
            'method' => 'POST',
        ));
        $form->add('submit', 'submit', array('label' => 'management_smtp.submit'));
        return $form;
    }


    /***
     * get data for file parameters_smtp.yml
     * @param $form
     * @return mixed
     */
    private function getData($form)
    {
        $value = Yaml::parse(file_get_contents(self::PATH));
        $form->get('transport')->setData($value['parameters']['mailer_transport']);
        $form->get('host')->setData($value['parameters']['mailer_host']);
        $form->get('port')->setData($value['parameters']['mailer_port']);
        $form->get('auth_mode')->setData($value['parameters']['mailer_auth_mode']);
        $form->get('user')->setData($value['parameters']['mailer_user']);
        $form->get('password')->setData($value['parameters']['mailer_password']);
        $form->get('email')->setData($value['parameters']['mailer_email']);
        $form->get('name')->setData($value['parameters']['mailer_name']);
        return $form;
    }


    /**
     * set data form from files parameter_stml.yml
     * @param $form
     */
    private function setData($form)
    {
        $array = array('parameters' => array(
            'mailer_transport' => $form->get('transport')->getData(),
            'mailer_host' => $form->get('host')->getData(),
            'mailer_port' => $form->get('port')->getData(),
            'mailer_auth_mode' => $form->get('auth_mode')->getData(),
            'mailer_user' => $form->get('user')->getData(),
            'mailer_password' => $form->get('password')->getData(),
            'mailer_email' => $form->get('email')->getData(),
            'mailer_name' => $form->get('name')->getData(),
        ));
        $yaml = Yaml::dump($array);
        file_put_contents(self::PATH, $yaml);
    }
}
