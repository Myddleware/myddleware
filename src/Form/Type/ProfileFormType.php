<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileFormType extends AbstractType
{
    /**
     * delete current_password.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', null, ['label' => 'form.username'])
            ->add('email', EmailType::class, ['label' => 'form.email'])
            ->add('timezone', TimezoneType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'regle_user_profile';
    }
}
