<?php

namespace App\Form\Type;
use App\Entity\Rule;
use App\Entity\Workflow;
use App\Repository\RuleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Range;




class WorkflowActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $builder
            ->add('name', TextType::class, [
                'label' => 'Action Name',
                'required' => true,
            ])
            ->add('description', TextType::class, ['label' => 'Description'])
            ->add('Workflow', EntityType::class, [
                'class' => Workflow::class,
                'choices' => $options['entityManager']->getRepository(Workflow::class)->findBy(['deleted' => 0]),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('action', TextType::class, ['label' => 'Action'])
            ->add('to', TextType::class, ['label' => 'To', 'mapped' => false])
            ->add('subject', TextType::class, ['label' => 'Subject', 'mapped' => false])
            ->add('message', TextType::class, ['label' => 'Message', 'mapped' => false])
            ->add('order', IntegerType::class, [
                'label' => 'Order',
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 50,
                        'notInRangeMessage' => 'You must enter a number between {{ min }} and {{ max }}.',
                    ]),
                ],
            ])
            ->add('active', ChoiceType::class, [
                'label' => 'Active',
                'choices' => [
                    'Yes' => 1,
                    'No' => 0,
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Save'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\WorkflowAction', // Replace with your actual Workflow entity class
            'entityManager' => null, // Allow the entityManager option
        ]);
    }
}