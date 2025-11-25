<?php

namespace App\Form\Type;
use App\Entity\RuleGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;




class RuleGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $builder
            ->add('name', TextType::class, [
                'label' => 'rulegroup.rulegroup_name',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'rulegroup.name_cannot_be_empty',
                        'normalizer' => 'trim',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'rulegroup.table.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'list_rule.save',
                'attr' => [
                    'class' => 'mt-2 btn btn-primary',
                    ],
                ]);
        
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RuleGroup::class,
            'entityManager' => null,
        ]);
    }
}
