<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserType extends AbstractType
{
    private const PRIVILEGED_ROLES = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roleChoices = [
            'Utilisateur' => 'ROLE_USER',
            'Admin' => 'ROLE_ADMIN',
            'Super Admin' => 'ROLE_SUPER_ADMIN',
        ];

        // A privileged role can only be granted or revoked by a user who holds it
        // (the role hierarchy applies). Fails closed: without an authenticated user,
        // every privileged role is locked.
        $lockedRoles = [];
        foreach (self::PRIVILEGED_ROLES as $role) {
            if (!$this->authorizationChecker->isGranted($role)) {
                $lockedRoles[] = $role;
            }
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
                'choice_attr' => function ($choice, $key, $value) use ($lockedRoles) {
                    return in_array($value, $lockedRoles, true)
                        ? ['disabled' => 'disabled']
                        : [];
                }
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'user_manager.table.timezone',
                'attr' => ['class' => 'form-select'],
                'row_attr' => ['class' => 'mb-3']
            ]);

        // Server-side enforcement of the locked roles: the HTML "disabled" attribute
        // is only cosmetic and can be bypassed by crafting the request.
        $originalRoles = [];
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use (&$originalRoles) {
            $user = $event->getData();
            $originalRoles = $user instanceof User ? $user->getRoles() : [];
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use (&$originalRoles, $lockedRoles) {
            $user = $event->getData();
            if (!$user instanceof User || empty($lockedRoles)) {
                return;
            }
            $submittedRoles = $user->getRoles();
            foreach ($lockedRoles as $role) {
                $hadRole = in_array($role, $originalRoles, true);
                $hasRole = in_array($role, $submittedRoles, true);
                if (!$hadRole && $hasRole) {
                    // Attempt to grant a role the current user is not allowed to grant
                    $user->removeRole($role);
                    $event->getForm()->get('roles')->addError(new FormError('You are not allowed to assign the role '.$role.'.'));
                } elseif ($hadRole && !$hasRole) {
                    // Disabled options are not submitted by the browser: keep the locked role
                    $user->addRole($role);
                }
            }
        });

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
            // Kept for backward compatibility; role restrictions now rely on the security token.
            'current_user' => null,
        ]);
    }
}
