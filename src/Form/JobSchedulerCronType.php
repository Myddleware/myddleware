<?php

namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSchedulerCronType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enable', CheckboxType::class, [
            'label' => 'Active',
            'required' => false,
            ])
            ->add('command', TextType::class, ['mapped' => false])
            ->add('description', TextType::class)
            ->add('runningInstances', IntegerType::class, ['mapped' => false])
            ->add('maxInstances', IntegerType::class)
             ->add('period', TextType::class, [
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^\s*\S+\s+\S+\s+\S+\s+\S+\s+\S+\s*$/',
                        'message' => 'Invalid cron expression. Example: */5 * * * *',
                    ]),
                ], 'attr' => [
                    'required' => true,
                    'placeholder' => '*/5 * * * *',
                    'pattern' => '\S+\s+\S+\s+\S+\s+\S+\s+\S+',
                    'title' => '5 fields separated by spaces (*/5 * * * *)',
                ],
            ])
            ->add('save', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-success',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Shapecode\Bundle\CronBundle\Entity\CronJob',
        ]);
    }
}
