<?php

namespace App\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RelationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field', ChoiceType::class, [
                'label' => 'Field',
                'attr' => [
                    'placeholder' => 'Field',
                ],
                'empty_data' => null,
                'choices' => [
                    'test1' => 'test1',
                    'test2' => 'test2',
                    'test3' => 'test3',
                ],
                'placeholder' => '- Choose a field -',
                'required' => false
            ])
            ->add('another_field', ChoiceType::class, [
                'label' => 'Another Field',
                'attr' => [
                    'placeholder' => 'Another Field',
                ],
                'empty_data' => null,
                'choices' => [
                    'test4' => 'test4',
                    'test5' => 'test5',
                    'test6' => 'test6',
                ],
                'placeholder' => '- Choose another field -',
                'required' => false
            ])
            ->add('textarea_field', TextType::class, [
                'label' => 'Your Text',
                'attr' => [
                    'placeholder' => 'Enter some text here...',
                ],
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
