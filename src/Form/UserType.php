<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Security\Core\User\UserInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'] ?? null;

        $roleChoices = [
            'Utilisateur' => 'ROLE_USER',
            'Admin' => 'ROLE_ADMIN',
            'Super Admin' => 'ROLE_SUPER_ADMIN',
        ];

        $disabledRoles = [];
        if ($currentUser && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            $disabledRoles[] = 'ROLE_SUPER_ADMIN';
        }
        if ($currentUser && !in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            $disabledRoles[] = 'ROLE_ADMIN';
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'user_manager.table.username',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('email', EmailType::class, [
                'label' => 'user_manager.table.email',
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'user_manager.table.roles',
                'choices' => $roleChoices,
                'multiple' => true,
                'expanded' => false,
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3'],
                'choice_attr' => function ($choice, $key, $value) use ($disabledRoles) {
                    return in_array($value, $disabledRoles, true)
                        ? ['disabled' => 'disabled']
                        : [];
                }
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'user_manager.table.timezone',
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ]);

        if ($options['include_password']) {
            $builder->add('password', PasswordType::class, [
                'label' => 'user_manager.password',
                'mapped' => false,
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'row_attr' => ['class' => 'mb-3']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password' => false,
            'current_user' => null,
        ]);
    }
}
