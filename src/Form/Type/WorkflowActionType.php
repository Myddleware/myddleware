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




class WorkflowActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $builder
            ->add('name', TextType::class, [
                'label' => 'Action Name',
                'required' => true,
            ]);
            $builder->add('description', TextType::class, ['label' => 'Description']);
            $builder->add('Workflow', EntityType::class, [
                'class' => Workflow::class,
                'choices' => $options['entityManager']->getRepository(Workflow::class)->findBy(['active' => true]),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new NotBlank(),
                ],
            ]);
            $builder->add('submit', SubmitType::class, ['label' => 'Save']);
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