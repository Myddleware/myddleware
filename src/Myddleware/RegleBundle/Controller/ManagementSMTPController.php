<?php

namespace Myddleware\RegleBundle\Controller;

use Myddleware\RegleBundle\Form\managementSMTPType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Myddleware\RegleBundle\Classes\tools as MyddlewareTools;
use Symfony\Bridge\Monolog\Logger; // Gestion des logs
class ManagementSMTPController extends Controller
{
    const PATH = './../app/config/parameters_smtp.yml';
    protected $tools;


    public function construct()
    {


    }

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

    /**
     * Get value in the parameters_smtp.yml
     * @param $parameter
     * @return mixed
     */
    private function getValueParameters($parameter)
    {
        $value = Yaml::parse(file_get_contents(self::PATH));
        return $value['parameters'][$parameter];
    }

    /**
     * Send mail for test configuration in the parameters_smtp.yml
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function testMailConfigurationAction()
    {
        $emailAddresses = $this->getValueParameters('mailer_email');
        $this->tools = new MyddlewareTools($this->get('logger'), $this->container, $this->get('database_connection'));
        $subject = $this->tools->getTranslation(array('management_smtp_sendmail', 'subject'));
        try {
            // Check that we have at least one email address
            if (empty($emailAddresses)) {
                throw new \Exception ('No email address found to send notification. You should have at leas one admin user with an email address.');
            }
            $contactMail = $this->getValueParameters('mailer_name');
            $textMail = $this->tools->getTranslation(array('management_smtp_sendmail', 'textMail')).chr(10);
            $textMail .= $this->tools->getTranslation(array('email_notification', 'best_regards')).chr(10).$this->tools->getTranslation(array('email_notification', 'signature'));
            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom('no-reply@myddleware.com')
                ->setBody($textMail);
            $message->setTo($emailAddresses);
            $send = $this->container->get('mailer')->send($message);
            if (!$send) {
                $this->logger->error('Failed to send email : ' . $textMail . ' to ' . $contactMail);
                throw new \Exception ('Failed to send email : ' . $textMail . ' to ' . $contactMail);
            }
            return $this->redirectToRoute('management_smtp_index');
        } catch (\Exception $e) {
            $error = 'Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )';
            throw new \Exception ($error);
        }
    }
}
