<?php

namespace Myddleware\RegleBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType ;

class managementSMTPType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('transport', TextType::class);
        $builder->add('host', TextType::class);
        $builder->add('port', TextType::class);
        $builder->add('auth_mode', ChoiceType::class, array(
            'empty_data' => 'null',
            'choices' => array(
                'plain' => 'plain',
                'login' => 'login',
                'cram-md5' => 'cram-md5',
            ),
            'empty_value' => '- Choice mode auth -'));
        $builder->add('user', TextType::class);
        $builder->add('password', PasswordType ::class);
        $builder->add('email', TextType::class);
        $builder->add('name', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getBlockPrefix()
    {
        return 'regle_bundlemanagement_smtp';
    }
}
