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
            ->add('host')
            ->add('port', IntegerType::class, [
                'required' => false,
            ])
            ->add('name')
            ->add('user')
            ->add('password', PasswordType::class, [
                'required' => false,          //this field needs to be nullable as localhost is often root with no password
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
