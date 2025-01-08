<?php

namespace App\Form\Type;
use App\Entity\Rule;
use App\Repository\RuleRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;




class WorkflowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $entity = $options['entity']; // Access the entity passed to the form

        if ($entity !== null) {
            $existingCondition = $entity->getCondition();
        } else {
            $existingCondition = false;
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Workflow Name',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    ],
            ]);
            $builder->add('description', TextType::class, ['label' => 'Description']);
            $builder->add('Rule', EntityType::class, [
                'class' => Rule::class,
                'choices' => $options['entityManager']->getRepository(Rule::class)->findBy(['deleted' => 0]),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    ],
            ]);
            $builder->add('active', ChoiceType::class, [
                'label' => 'Active',
                'choices' => [
                    'Yes' => 1,
                    'No' => 0,
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
            $builder->add('order', IntegerType::class, [
                'label' => 'Order',
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 50,
                        'notInRangeMessage' => 'You must enter a number between {{ min }} and {{ max }}.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    ],
                ]);
                $builder->add('condition', TextareaType::class, [
                    'label' => 'Condition',
                    'data' => $existingCondition ?: '{status} == "', // Use existing condition if available, otherwise use default
                    'constraints' => [
                        new Callback([
                            'callback' => function($payload, ExecutionContextInterface $context) {
                                // Check for both possible patterns
                                if (strpos($payload, '{status} == "') === false && strpos($payload, '{status}=="') === false) {
                                    $context->buildViolation('The condition must contain either "{status} == " or "{status}==""')
                                        ->atPath('condition')
                                        ->addViolation();
                                }
                                // Check for the specific pattern indicating an empty condition
                                if (preg_match('/\(\{status\} == " ?\?"1":"0"\)/', $payload)) {
                                    $context->buildViolation('The condition is empty')
                                        ->atPath('condition')
                                        ->addViolation();
                                }
                                if (trim($payload) === '{status} == "' || trim($payload) === '{status}=="') {
                                    $context->buildViolation('The condition is incomplete. Please add a value to compare with.')
                                        ->atPath('condition')
                                        ->addViolation();
                                }
                            },
                        ]),
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ]);
            $builder->add('submit', SubmitType::class, [
                'label' => 'Save',
                'attr' => [
                    'class' => 'mt-1  mb-4 btn btn-primary',
                    ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Workflow', // Replace with your actual Workflow entity class
            'entityManager' => null, // Allow the entityManager option
            'entity' => null, // Allow the entity option
        ]);
    }
}