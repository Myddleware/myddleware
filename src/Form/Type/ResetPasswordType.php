<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Form\Model\ResetPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

/**
 * Class ResetPasswordType
 * @package App\Form
 */
class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('plainPassword', PasswordType::class, array(
                                // 'mapped' => User::class,
                                // 'mapped' => false,
                                // 'label' => 'Old Password',
                                'required' => true
                            ))
            ->add('password', RepeatedType::class, [
                'required' => true,
                'type' => PasswordType::class,
                'invalid_message' => 'profile.type.password.invalid_message',
                'options' => [
                    'attr' => [
                        'class' => 'input',
                    ],
                ],
                'first_options'  => [
                    'label' => 'profile.type.password.first_options',
                ],
                'second_options' => [
                    'label' => 'profile.type.password.second_options',
                ],
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'forms',
        ]);
    }
}









// namespace App\Form\Type;

// use App\Entity\User;
// use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\FormBuilderInterface;
// use Symfony\Component\OptionsResolver\OptionsResolver;
// use Symfony\Component\Validator\Constraints as Assert;
// use Symfony\Component\Form\Extension\Core\Type\SubmitType;
// use Symfony\Component\Form\Extension\Core\Type\PasswordType;
// use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

// class ResetPasswordType extends AbstractType
// {
//     public function buildForm(FormBuilderInterface $builder, array $options)
//     {
//         $builder
//             ->add('oldPassword', PasswordType::class, array(
//                 'mapped' => false,
//                 'required' => true
//             ))
//             ->add('plainPassword', RepeatedType::class, [
//                 'type' => PasswordType::class,
//                 'invalid_message' => 'Both passwords must be identical',
//                 'required' => true,
//             ])
//             ->add('submit', SubmitType::class)
//         ;
//     }

//     public function configureOptions(OptionsResolver $resolver)
//     {
//         $resolver->setDefaults([
//             'data_class' => User::class,
//         ]);
//     }

//     public function getBlockPrefix()
//     {
//         return 'reset_password';
//     }
// }
