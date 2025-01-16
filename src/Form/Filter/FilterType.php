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
                'choices' => [
                    '- Select your filters -' => 'default',
                    'Reference' => 'reference',
                    'Date of modification Start' => 'date_modif_start',
                    'Date of modification End' => 'date_modif_end',
                    'Module source' => 'moduleSource',
                    'Module target' => 'moduleTarget',
                    'Target id' => 'target',
                    'Status' => 'status',
                    'Type' => 'type',
                    'Source Content' => 'sourceContent',
                    'Target Content' => 'targetContent',
                    'Message' => 'message',
                ],
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