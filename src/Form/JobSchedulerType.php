<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSchedulerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('command', ChoiceType::class, [
                'required' => true,
                'empty_data' => 'null',
                'choices' => [
                    'Synchro' => 'synchro',
                    'Notification' => 'notification',
                    'Rerun Error' => 'rerunerror',
                    'Clear Data' => 'cleardata',
                ],
                'placeholder' => '- Choose command -',
            ])
            ->add('paramName1')
            ->add('paramValue1', ChoiceType::class)
            ->add('paramValue2')
            ->add('paramName2')
            ->add('period', IntegerType::class, ['required' => true, 'data' => $this->getPeriod($options)])
            ->add('active', CheckboxType::class, [
                'label' => 'Active ?',
                'required' => false,
            ])
            ->add('jobOrder', IntegerType::class, ['required' => true, 'data' => $this->getJobOrder($options)]);
    }

    public function getPeriod($options): int
    {
        return '' == $options['data']->getPeriod() ? $period = 5 : $period = $options['data']->getPeriod();
    }

    public function getJobOrder($options): int
    {
        return '' == $options['data']->getjobOrder() ? $jobOrder = 1 : $jobOrder = $options['data']->getjobOrder();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\JobScheduler',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'myddleware_reglebundle_jobscheduler';
    }
}
