<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

        ->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Passwords must be identical',
            'options' => [
                'attr' => [
                    'class' => 'password-field white-reset',
                    'style' => ' background-color: #fafafa;
                    border-radius: 4px;
                    padding: 10px;
                    max-width: 500px;
                    width: 100%;
                    text-align: center;
                    font-family: Arial, sans-serif; border: 1px solid #dcdcdc;
                    margin-top: 2px;
                    margin-bottom: 10px;'
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
        ->add('oldPassword', PasswordType::class, [
            'mapped' => false,
            'required' => true,
            'label' => 'password_reset.old_password',
            'attr' => [
                'class' => 'password-field white-reset',
                'style' => ' background-color: #fafafa;
                    border-radius: 4px;
                    padding: 10px;
                    max-width: 500px;
                    width: 100%;
                    text-align: center;
                    font-family: Arial, sans-serif; border: 1px solid #dcdcdc'
            ],
        ])
        ->add('submit', SubmitType::class, [
            'attr' => [
                'class' => 'mt-3 btn-lg',
                'style' => 'background-color: #03c4eb; border: none; font-size: 18px; padding: 12px; border-radius: 4px; color: #fff; transition: background-color 0.3s; display: block; margin: 0 auto;',
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
