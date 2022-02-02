<?php

namespace App\Form;

use App\Entity\Connector;
use App\Entity\Rule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DuplicateRuleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('connectorSource', EntityType::class,[
                'class' => Connector::class,
                'choice_label'=> 'name',
                'label' => 'Connector source' 
            ])
            ->add('connectorTarget', EntityType::class,[
                'class' => Connector::class,
                'choice_label'=> 'name',
                'label' => 'Connector source' 
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-outline-success mb-2'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rule::class,
        ]);
    }
}
