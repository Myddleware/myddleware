<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('oldPassword', PasswordType::class, array(
            'mapped' => false,
            'required' => true,
            'label' => 'password_reset.old_password'
        ))
        ->add('plainPassword', RepeatedType::class, array(
            'type' => PasswordType::class,
            'invalid_message' => 'Passwords must be identical',
            'options' => array(
                'attr' => array(
                    'class' => 'password-field'
                )
            ),
            'first_options'  => [
                'label' => 'password_reset.first_options',
            ],
            'second_options' => [
                'label' => 'password_reset.second_options',
            ],
            'required' => true,
        ))
        ->add('submit', SubmitType::class, array(
            'attr' => array(
                'class' => 'btn btn-primary btn-block'
            ),
            'label' => 'password_reset.submit'

        ))

    ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
