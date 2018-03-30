<?php

namespace Myddleware\RegleBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JobSchedulerType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('command', ChoiceType::class, array(
                'required' => true,
                'empty_data' => 'null',
                'choices' => array(
                    'synchro' => 'Synchro',
                    'notification' => 'Notification',
                    'rerunerror' => 'reRunError',
                ),
                'empty_value' => '- Choice command -',

            ))
            ->add('paramName1')
            ->add('paramValue1')
            ->add('paramValue2')
            ->add('paramName2')
            ->add('period', IntegerType::class, array('required' => true, 'data' => 5))
            ->add('active', CheckboxType::class, array(
                'label' => 'Active ?',
                'required' => false,
            ))
            ->add('jobOrder', IntegerType::class, array('required' => true, 'data' => 1));


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
