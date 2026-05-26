<?php
// ItemFilterType.php
namespace App\Form\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Doctrine\ORM\EntityManagerInterface;


class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('filter', Filters\ChoiceFilterType::class, [
                'label' => 'document.filters.label',
                'placeholder' => 'document.filters.default',
                'required' => false,
                'choices' => [
                    'document.filters.reference' => 'reference',
                    'document.filters.date_modif_start' => 'date_modif_start',
                    'document.filters.date_modif_end' => 'date_modif_end',
                    'document.filters.module_source' => 'moduleSource',
                    'document.filters.module_target' => 'moduleTarget',
                    'document.filters.target' => 'target',
                    'document.filters.status' => 'status',
                    'document.filters.type' => 'type',
                    'document.filters.source_content' => 'sourceContent',
                    'document.filters.target_content' => 'targetContent',
                    'document.filters.message' => 'message',
                ],
                'choice_translation_domain' => 'messages',
                'data' => 'default',
                'attr' => [
                    'class' => 'form-control',
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