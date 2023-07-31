<?php
namespace App\Form\Filter;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;



class SourceDataFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('sourceContent',  TextType::class, [
            'attr' => [
                'placeholder' => 'Source Content',
                'class' => 'form-control mt-2',
                'id' => 'sourceContent'
            ],
        ])
        ->add('targetContent',  TextType::class, [
            'attr' => [
                'placeholder' => 'Target Content',
                'class' => 'form-control mt-2',
                'id' => 'targetContent'
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'item_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering'), // avoid NotBlank() constraint-related message
        ));
    }
}