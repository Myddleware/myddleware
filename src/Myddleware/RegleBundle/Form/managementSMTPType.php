<?php

namespace Myddleware\RegleBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class managementSMTPType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('transport', TextType::class, array('required' => false));
        $builder->add('host', TextType::class, array('required' => false));
        $builder->add('port', TextType::class, array('required' => false));
        $builder->add('auth_mode', ChoiceType::class, array(
            'empty_data' => 'null',
            'required' => false,
            'choices' => array(
                'plain' => 'plain',
                'login' => 'login',
                'cram-md5' => 'cram-md5',
            ),
            'empty_value' => '- Choice mode auth -'));
        $builder->add('user', TextType::class, array('required' => false));
        $builder->add('password', PasswordType ::class, array('required' => false));
        $builder->add('email', TextType::class, array('required' => false));
        $builder->add('name', TextType::class, array('required' => false));
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getBlockPrefix()
    {
        return 'regle_bundlemanagement_smtp';
    }
}
