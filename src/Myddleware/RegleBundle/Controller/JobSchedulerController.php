<?php

namespace Myddleware\RegleBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Myddleware\RegleBundle\Entity\JobScheduler;
use Myddleware\RegleBundle\Form\JobSchedulerType;
// Include JSON Response
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * JobScheduler controller.
 *
 */
class JobSchedulerController extends Controller
{


    /**
     * Lists all JobScheduler entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('RegleBundle:JobScheduler');
        if (!$entities) {
            //   var_dump("not exist");
        } else {
            // var_dump("exist");

        }
        return $this->render('RegleBundle:JobScheduler:index.html.twig', array(
            'entities' => $entities,
        ));
    }

    /**
     * Creates a new JobScheduler entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new JobScheduler();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $paramName1 = $form->get('paramName1')->getData();
            $paramName1 = $paramName1 ? $paramName1 : "";

            $paramValue1 = $form->get('paramValue1')->getData();
            $paramValue1 = $paramValue1 ? $paramValue1 : "";


            $paramName2 = $form->get('paramName2')->getData();
            $paramName2 = $paramName2 ? $paramName2 : "";

            $paramValue2 = $form->get('paramValue2')->getData();
            $paramValue2 = $paramValue2 ? $paramValue2 : "";

            $active = $form->get('active')->getData() == 1 ? true : false;

            /**
             * set value by default
             */
            $entity->setDateCreated(new \DateTime());
            $entity->setCreatedBy($this->getUser()->getId());
            $entity->setModifiedBy($this->getUser()->getId());
            $entity->setParamName1($paramName1);
            $entity->setParamValue1($paramValue1);
            $entity->setParamName2($paramName2);
            $entity->setParamValue2($paramValue2);
            $entity->setActive($active);
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('jobscheduler_show', array('id' => $entity->getId())));
        }

        return $this->render('RegleBundle:JobScheduler:new.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a JobScheduler entity.
     *
     * @param JobScheduler $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(JobScheduler $entity)
    {
        $form = $this->createForm(new JobSchedulerType(), $entity, array(
            'action' => $this->generateUrl('jobscheduler_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new JobScheduler entity.
     *
     */
    public function newAction()
    {
        $entity = new JobScheduler();
        $form = $this->createCreateForm($entity);

        return $this->render('RegleBundle:JobScheduler:new.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a JobScheduler entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RegleBundle:JobScheduler')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('RegleBundle:JobScheduler:show.html.twig', array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing JobScheduler entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RegleBundle:JobScheduler')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('RegleBundle:JobScheduler:edit.html.twig', array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a JobScheduler entity.
     *
     * @param JobScheduler $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(JobScheduler $entity)
    {
        $form = $this->createForm(new JobSchedulerType(), $entity, array(
            'action' => $this->generateUrl('jobscheduler_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing JobScheduler entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RegleBundle:JobScheduler')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('jobscheduler_edit', array('id' => $id)));
        }

        return $this->render('RegleBundle:JobScheduler:edit.html.twig', array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a JobScheduler entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('RegleBundle:JobScheduler')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find JobScheduler entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('jobscheduler'));
    }

    /**
     * Creates a form to delete a JobScheduler entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('jobscheduler_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm();
    }

    public function getFieldsSelectAction(Request $request)
    {
        if ($request->isXmlHttpRequest() && $request->getMethod() == 'GET') {
            $em = $this->getDoctrine()->getEntityManager();

            $select = $this->getData($request->query->get("type"));
            // create array for json response
//            $empoloyees = array();
            return new JsonResponse($select);

//            $response = new Response(json_encode(2));
//            $response->headers->set('Content-Type', 'application/json');
//            return $response;
        }
    }

    public $jobList = array('cleardata', 'notification', 'rerunerror', 'synchro');

    public function getJobsParams()
    {
        try {
            $list = array();
            if (!empty($this->jobList)) {
                foreach ($this->jobList as $job) {
                    $list[$job]['name'] = $job;
                    switch ($job) {
                        case 'synchro':
                            $list[$job]['param1'] = array(
                                'rule' => array(
                                    'fieldType' => 'list',
                                    'option' => array('ALL' => 'All active rules', 'TEST' => ' TEST ME')  // Je vais ajouter toutes les règles dans la liste
                                )
                            );
                            $list[$job]['param2'] = array(
                                'rule' => array(
                                    'fieldType' => 'list',
                                    'option' => array('ALL' => 'All active rules', 'TEST' => ' TEST ME')  // Je vais ajouter toutes les règles dans la liste
                                )
                            );
                            break;
                        case 'notification':
                            $list[$job]['param1'] = array(
                                'type' => array(
                                    'fieldType' => 'list',
                                    'option' => array('alert' => 'alert', 'statistics' => 'statistics')
                                )
                            );
                            break;
                        case 'rerunerror':
                            $list[$job]['param1'] = array(
                                'limit' => array(
                                    'fieldType' => 'int'
                                )
                            );
                            $list[$job]['param1'] = array(
                                'attempt' => array(
                                    'fieldType' => 'int'
                                )
                            );
                            break;
                    }
                }
            }
            return $list;
        } catch (\Exception $e) {
            throw new \Exception ('Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
        }
    }

    private function getData($selectName)
    {
        $paramsCommand = null;
        if (isset($this->getJobsParams()[$selectName])) {
            $paramsCommand = $this->getJobsParams()[$selectName];
        } else {
            $paramsCommand = null;
        }
        return $paramsCommand;

    }
}
