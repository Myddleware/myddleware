<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ConnectorParam;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectorParamFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', EntityType::class, [
                'label' => 'Name',
                'class' => ConnectorParam::class,
                'choice_label' => 'name',
                'empty_data' => '',
            ])
            ->add('value', TextType::class, [
                'label' => 'Value',
                'empty_data' => '',
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConnectorParam::class,
        ]);
    }
}
