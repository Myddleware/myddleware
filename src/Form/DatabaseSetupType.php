<?php

namespace App\Form;

use App\Entity\DatabaseParameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatabaseSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('host', null, [
                'attr' => [
                    'placeholder' => 'eg: localhost',
                ],
            ])
            ->add('port', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'eg: 3306',
                ],
            ])
            ->add('name', null, [
                'label' => 'Database name',
                'attr' => [
                    'placeholder' => 'eg: my_database_name',
                ],
            ])
            ->add('user', null, [
                'attr' => [
                    'placeholder' => 'eg: root',
                ],
            ])
            ->add('password', PasswordType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Database password',
                ],
            ])
            ->add('Save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-success btn-lg',
                ],
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
