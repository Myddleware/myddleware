<?php

namespace Myddleware\RegleBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JobSchedulerType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        $builder
            ->add('command', ChoiceType::class, array(
                'choices' => array(

                    'clearData' => 'clear Data',
                    'synchro' => 'Synchro',
                    'generateTemplate' => 'Generate template',
                    'jobScheduler' => 'jobScheduler',
                    'mass' => 'Mass',
                    'notification' => 'Notification',
                    'rerunerror' => 'reRunError',
                    'task' => 'Task',
                    'upgrade' => 'Upgrade',
                ),
                'empty_value' => '- Choice command -',
            ))
            ->add('paramName1')
            ->add('paramValue1')
            ->add('paramValue2')
            ->add('paramName2')
            ->add('period')
//            ->add('lastRun')
            ->add('active', CheckboxType::class, array(
                'label' => 'Active ?',
                'required' => false,
            ))
            ->add('jobOrder');


    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        dump($data);
        $form = $event->getForm();
        $location = $event->getData();
        //dynamilcaly fill fields
    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        dump($data);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Myddleware\RegleBundle\Entity\JobScheduler'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'myddleware_reglebundle_jobscheduler';
    }
}
