<?php

namespace Myddleware\RegleBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        $entities = $em->getRepository('RegleBundle:JobScheduler')->findBy([], ['jobOrder' => 'ASC']);
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
            $entity->setDateModified(new \DateTime());
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

            return $this->redirect($this->generateUrl('jobscheduler', array('id' => $entity->getId())));
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
        $form = $this->createForm(JobSchedulerType::class, $entity, array(
            'action' => $this->generateUrl('jobscheduler_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'jobscheduler.new'));
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


        $user_created = $this->get('fos_user.user_manager')->findUsers(array('id' => $entity->getcreatedBy()));
        $user_modified = $this->get('fos_user.user_manager')->findUsers(array('id' => $entity->getmodifiedBy()));
        return $this->render('RegleBundle:JobScheduler:show.html.twig', array(
            'entity' => $entity,
            'user_created' => $user_created[0],
            'user_modified' => $user_modified[0]
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
        $form = $this->createForm(JobSchedulerType::class, $entity, array(
            'action' => $this->generateUrl('jobscheduler_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'jobscheduler.update'));
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

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            return $this->redirect($this->generateUrl('jobscheduler'));
        }

        return $this->render('RegleBundle:JobScheduler:edit.html.twig', array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a JobScheduler entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $id = $request->get('id');
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('RegleBundle:JobScheduler')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JobScheduler entity.');
        }

        $em->remove($entity);
        $em->flush();

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
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm();
    }

    /**
     * get fields select
     * @param Request $request
     * @return JsonResponse
     */
    public function getFieldsSelectAction(Request $request)
    {
        if ($request->isXmlHttpRequest() && $request->getMethod() == 'GET') {
            $select = $this->getData($request->query->get("type"));
            return new JsonResponse($select);
        }
    }


    private function getData($selectName)
    {
        $jobScheduler = $this->container->get('myddleware.jobScheduler');
        $paramsCommand = null;
        if (isset($jobScheduler->getJobsParams()[$selectName])) {
            $paramsCommand = $jobScheduler->getJobsParams()[$selectName];
        } else {
            $paramsCommand = null;
        }
        return $paramsCommand;
    }
}
