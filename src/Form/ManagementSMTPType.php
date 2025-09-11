<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ManagementSMTPType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('transport', ChoiceType::class, [
            'empty_data' => null,
            'choices' => [
                'smtp' => 'smtp',
                'gmail' => 'gmail',
                'sendmail' => 'sendmail',
                'sendinblue' => 'sendinblue',
            ],
            'placeholder' => '- Choose transport -',
            'required' => false, ]);

        $builder->add('host', TextType::class, ['required' => false]);
        $builder->add('port', IntegerType::class, ['required' => false]);
        $builder->add('ApiKey', PasswordType::class, [
            'required' => false,
            'attr' => ['autocomplete' => 'new-password']
        ]);
        $builder->add('auth_mode', ChoiceType::class, [
            'empty_data' => null,
            'choices' => [
                'plain' => 'plain',
                'login' => 'login',
                'cram-md5' => 'cram-md5',
                'oauth' => 'oauth',
            ],
            'placeholder' => '- Choose auth mode -',
            'required' => false, ]);

        $builder->add('encryption', ChoiceType::class, [
            'empty_data' => null,
            'choices' => [
                'tls' => 'tls',
                'ssl' => 'ssl',
            ],
            'placeholder' => '- Choose encryption -',
            'required' => false, ]);

        $builder->add('user', TextType::class, ['required' => false]);
        $builder->add('password', PasswordType ::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getBlockPrefix(): string
    {
        return 'regle_bundlemanagement_smtp';
    }
}
