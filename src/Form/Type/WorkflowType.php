<?php

namespace App\Form\Type;
use App\Entity\Rule;
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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Form\FormEvents;



class WorkflowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
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
                'choices' => $options['entityManager']->getRepository(Rule::class)->findBy(['active' => true]),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    ],
            ]);
            $builder->add('condition', TextareaType::class, [
                'label' => 'Condition',
                'data' => '{status} == "',
                'constraints' => [
                    new Callback([
                        'callback' => function($payload, ExecutionContextInterface $context) {
                            if (strpos($payload, '{status} == "') === false) {
                                $context->buildViolation('The string "{status} == " must be present in the condition')
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
            $builder->get('condition')->addEventListener(FormEvents::SUBMIT, function ($event) {
                $data = $event->getData();
                $ternaryOperator = ' ?"1":"0")';
                $lastPos = strrpos($data, $ternaryOperator);
                $firstPos = strpos($data, $ternaryOperator);
                if ($lastPos !== $firstPos) {
                    $data = substr_replace($data, '', $lastPos, strlen($ternaryOperator));
                }
                if (strrpos($data, $ternaryOperator) !== strlen($data) - strlen($ternaryOperator)) {
                    $data .= $ternaryOperator;
                }
                if ($data[0] !== '(') {
                    $data = '(' . $data;
                }
                $event->setData($data);
            });
            $builder->add('submit', SubmitType::class, [
                'label' => 'Save',
                'attr' => [
                    'class' => 'mt-2 btn btn-primary',
                    ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Workflow', // Replace with your actual Workflow entity class
            'entityManager' => null, // Allow the entityManager option
        ]);
    }
}