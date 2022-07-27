<?php

namespace App\Form;

use App\Entity\Solution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows us to display the list of solutions even when Entities aren't explicitly related to each other
 * E.g. in ConnectorParams Crud (credentials), we need to first select the solution
 * before being shown the corresponding login fields.
 */
class SolutionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('solution', EntityType::class, [
                'label' => 'Solution',
                'class' => Solution::class,
                'choice_label' => 'name',
                'empty_data' => '',
                'row_attr' => ['data-controller' => 'solution'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Solution::class,
        ]);
    }
}
