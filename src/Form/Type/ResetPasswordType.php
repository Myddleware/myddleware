<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

        ->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Passwords must be identical',
            'options' => [
                'attr' => [
                    'class' => 'password-field',
                ],
            ],
            'first_options' => [
                'label' => 'password_reset.first_options',
            ],
            'second_options' => [
                'label' => 'password_reset.second_options',
            ],
            'required' => true,
        ])
        ->add('submit', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-success mt-3 btn-lg',
            ],
            'label' => 'password_reset.submit',
        ])

    ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
