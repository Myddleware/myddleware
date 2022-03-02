<?php

namespace App\Form;

use App\Entity\Rule;
use App\Entity\Solution;
use App\Entity\Connector;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DuplicateRuleFormType extends AbstractType
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $solutionSource = $options['solutionSource'];
        $solutionTarget = $options['solutionTarget'];
        $builder
            ->add('name', TextType::class)
            ->add('connectorSource', EntityType::class,[
                'class' => Connector::class,
                'choice_label'=> 'name',
                'label' => 'Connector source',
                'query_builder' => function (EntityRepository $er) use($solutionTarget) {
					return $er->createQueryBuilder('c')
                        ->leftJoin('c.solution', 's')
						 ->where('s.id = :solution_id')
						 ->setParameter('solution_id', $solutionTarget);
                },
            ])
             ->add('connectorTarget', EntityType::class,[
                 'class' => Connector::class,
                 'choice_label'=> 'name',
                 'label' => 'Connector source',                   
                 'query_builder' => function (EntityRepository $er) use($solutionSource) {
					return $er->createQueryBuilder('c')
						 ->leftJoin('c.solution', 's')
						 ->where('s.id = :solution_id')
						 ->setParameter('solution_id', $solutionSource);
                },
             ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-outline-success mb-2'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('solutionTarget');
        $resolver->setRequired('solutionSource');
        $resolver->setAllowedTypes('solution', array(Solution::class, 'int'));
        $resolver->setDefaults([
            'data_class' => Rule::class,
        ]);
    }
}
