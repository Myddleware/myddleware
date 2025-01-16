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
                    'id' => 'field_filter_rule',
                ],
                'empty_data' => null,
                'choices' => $options['field_choices'],
                'placeholder' => '- Field to filter -',
                'required' => false
            ])
            ->add('another_field', ChoiceType::class, [
                'label' => 'Comparison Operator',
                'attr' => [
                    'placeholder' => 'Another Field',
                    'id' => 'another_field_filter_rule',
                ],
                'empty_data' => null,
                'choices' => $options['another_field_choices'],
                'placeholder' => '- Comparison Operators -',
                'required' => false
            ])
            ->add('textarea_field', TextType::class, [
                'label' => 'Field Value',
                'attr' => [
                    'placeholder' => 'Enter your field value here...',
                    'id' => 'textarea_field_filter_rule',
                ],
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'field_choices' => [],
            'another_field_choices' => [],
        ]);
    }
}
