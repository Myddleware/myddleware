<?php

namespace App\Form\Filter;

use App\Entity\Rule;
use App\Repository\RuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;


class RuleFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $builder
            ->add('moduleSource', Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findModuleSource($entityManager),
                
                'attr' => [
                    'placeholder' => 'Module source',
                    'class' => 'form-control mt-2',
                    'id' => 'moduleSource'
                ],
            ])
            ->add('moduleTarget', Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findModuleTarget($entityManager),
                'attr' => [
                    'placeholder' => 'Module target',
                    'class' => 'form-control mt-2',
                    'id' => 'moduleTarget'
                ],
            ])
            ->add('name',  Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findActiveRulesNames($entityManager),
                'attr' => [
                    'placeholder' => 'Name',
                    'class' => 'form-control mt-2',
                    'id' => 'name'
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('connectorSource', TextType::class, [
                'attr' => [
                    'placeholder' => 'Connector source',
                    'class' => 'form-control mt-2',
                    'id' => 'connectorSource'
                ],
            ])
            ->add('connectorTarget', TextType::class, [
                'attr' => [
                    'placeholder' => 'Connector target',
                    'class' => 'form-control mt-2',
                    'id' => 'connectorTarget'
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
            'data_class' => Rule::class,
        ));

        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', EntityManagerInterface::class);
    }
}