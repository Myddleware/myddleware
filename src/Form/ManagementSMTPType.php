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
                'SMTP' => 'smtp',
                'Gmail' => 'gmail',
                'Sendmail' => 'sendmail',
                'Sendinblue' => 'sendinblue',
            ],
            'placeholder' => 'smtp_config.choose_transport',
            'required' => false,
            'choice_translation_domain' => 'messages',
        ]);

        $builder->add('host', TextType::class, ['required' => false]);
        $builder->add('port', IntegerType::class, ['required' => false]);
        $builder->add('ApiKey', PasswordType::class, [
            'required' => false,
            'attr' => ['autocomplete' => 'new-password']
        ]);
        $builder->add('auth_mode', ChoiceType::class, [
            'empty_data' => null,
            'choices' => [
                'smtp_config.plain' => 'plain',
                'smtp_config.login' => 'login',
                'smtp_config.cram-md5' => 'cram-md5',
                'smtp_config.oauth' => 'oauth',
            ],
            'placeholder' => 'smtp_config.choose_auth_mode',
            'required' => false,
            'choice_translation_domain' => 'messages',
        ]);

        $builder->add('encryption', ChoiceType::class, [
            'empty_data' => null,
            'choices' => [
                'smtp_config.tls' => 'tls',
                'smtp_config.ssl' => 'ssl',
            ],
            'placeholder' => 'smtp_config.choose_encryption',
            'required' => false,
            'choice_translation_domain' => 'messages',
        ]);

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
