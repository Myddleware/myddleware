<?php
namespace App\Form\Type;

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
                'hidden'=> 'true',
                'placeholder' => 'Source Content',
                'class' => 'form-control mt-2',
                'id' => 'sourceContent'
            ],
        ])
        ->add('targetContent',  TextType::class, [
            'attr' => [
                'hidden'=> 'true',
                'placeholder' => 'Target Content',
                'class' => 'form-control mt-2',
                'id' => 'targetContent'
            ],
        ])
        ->add('operator', Filters\ChoiceFilterType::class, [
             'choices' => [
                '- Select your filters -' => 'default',
                // 'Id' => 'id',
                'Rule name' => 'name',
                // 'Reference date start' => 'sourceDateModified',
                'Date of modification Start' => 'date_modif_start',
                'Date of modification End' => 'date_modif_end',
                // 'Date Created' => 'dateCreated',
                'Module source' => 'moduleSource',
                'Module target' => 'moduleTarget',
                // 'Connector source' => 'connectorSource',
                // 'Connector target' => 'connectorTarget',
                'Source id' => 'source',
                'Target id' => 'target',
                'Status' => 'status',
                'Global Status' => 'globalStatus',
                'Type' => 'type',
                'Source Content' => 'sourceContent',
                'Target Content' => 'targetContent',
            ],
            'attr' => [
                'class' => 'form-control mt-2',
                'id' => 'operator',
                'placeholder' => 'operator',
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