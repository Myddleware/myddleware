<?php

namespace App\Form\Filter;

use App\Form\Filter\DocFilterType;
use App\Form\Filter\RuleFilterType;
use App\Form\Filter\SourceDataFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CombinedFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('document', DocFilterType::class, [
            'required' => false,
            'entityManager' => $options['entityManager'],
        ]);
        $builder->add('rule', RuleFilterType::class, [
            'required' => false,
            'entityManager' => $options['entityManager'],
        ]);
        $builder->add('sourceContent', SourceDataFilterType::class, [
            'required' => false,
        ]);
        // add save button to builder
        $builder->add('save', SubmitType::class, [
            'label' => 'Search', 
            'attr' => ['class' => 'btn btn-primary mb-2'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'entityManager' => null,
        ]);
    }
}
