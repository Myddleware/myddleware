<?php

namespace Myddleware\RegleBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
                    'rerunerror' => 'Rerun Error',
                    'cleardata' => 'Clear Data',
                ),
                'placeholder' => '- Choice command -',

            ))
            ->add('paramName1')
            ->add('paramValue1')
            ->add('paramValue2')
            ->add('paramName2')
            ->add('period', IntegerType::class, array('required' => true, 'data' => $this->getPeriod($options)))
            ->add('active', CheckboxType::class, array(
                'label' => 'Active ?',
                'required' => false,
            ))
            ->add('jobOrder', IntegerType::class, array('required' => true, 'data' => $this->getJobOrder($options)));
    }

    /**
     * get period value
     * @param $options
     * @return int
     */
    public function getPeriod($options)
    {
        return $options['data']->getPeriod() == '' ? $period = 5 : $period = $options['data']->getPeriod();
    }

    /**
     * get jobOrder value
     * @param $options
     * @return int
     */
    public function getJobOrder($options)
    {
        return $options['data']->getjobOrder() == '' ? $jobOrder = 1 : $jobOrder = $options['data']->getjobOrder();
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions (OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Myddleware\RegleBundle\Entity\JobScheduler'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'myddleware_reglebundle_jobscheduler';
    }
}
