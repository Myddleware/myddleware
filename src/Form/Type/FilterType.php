<?php
// ItemFilterType.php
namespace App\Form\Type;

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
                    'Id' => 'id',
                    'Rule name' => 'name',
                    'Date of modification' => 'dateModified',
                    'Date start' => 'dateCreated',
                    'Module source' => 'moduleSource',
                    'Module target' => 'moduleTarget',
                    'Connector source' => 'connectorSource',
                    'Connector target' => 'connectorTarget',
                    'Source id' => 'sourceId',
                    'Target id' => 'targetId',
                    'Status' => 'status',
                    'Type' => 'type',
                ],

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