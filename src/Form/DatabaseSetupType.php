<?php

namespace App\Form;

use App\Entity\DatabaseParameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DatabaseSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('driver')
            ->add('host')
            ->add('port', IntegerType::class)
            ->add('name')
            ->add('user')
            ->add('password')
            ->add('OK', SubmitType::class,[
                'attr' => [
                    'class' => 'btn btn-success btn-block',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DatabaseParameter::class,
        ]);
    }
}
