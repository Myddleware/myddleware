<?php

namespace App\Form;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Form\ConnectorParamFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ConnectorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('connectorParams', CollectionType::class, [
                'entry_type' => ConnectorParamFormType::class,
                'entry_options' => ['label' => false],
                'row_attr' => [ 'class' => 'p-3']
            ])
            // ->add('name')
            // ->add('nameSlug')
            // ->add('deleted')
            // ->add('createdAt')
            // ->add('updatedAt')
            // ->add('createdBy')
            // ->add('modifiedBy')
            // ->add('solution')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Connector::class,
        ]);
    }
}
