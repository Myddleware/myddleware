<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\DocFilterType;
use App\Form\Type\ItemFilterType;

class CombinedFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('document', DocFilterType::class, [
            'required' => false,
            'entityManager' => $options['entityManager'],
        ]);
        $builder->add('rule', ItemFilterType::class, [
            'required' => false,
            'entityManager' => $options['entityManager'],
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
