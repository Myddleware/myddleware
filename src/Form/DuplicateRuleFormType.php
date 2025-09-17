<?php

namespace App\Form;

use App\Entity\Connector;
use App\Entity\Rule;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DuplicateRuleFormType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $solutionSources = $options['solution']['source'];
        $solutionTargets = $options['solution']['target'];
        $builder
            ->add('name', TextType::class)
            ->add('connectorSource', EntityType::class, [
                'class' => Connector::class,
                'choice_label' => 'name',
                'label' => 'Connector source',
                'query_builder' => function (EntityRepository $er) use ($solutionSources) {
                    return $er->createQueryBuilder('c')
                        ->leftJoin('c.solution', 's')
                         ->where('s.id = :solution_id')
                         ->andWhere('c.deleted = 0')
                         ->setParameter('solution_id', $solutionSources);
                },
            ])
             ->add('connectorTarget', EntityType::class, [
                 'class' => Connector::class,
                 'choice_label' => 'name',
                 'label' => 'Connector source',
                 'query_builder' => function (EntityRepository $er) use ($solutionTargets) {
                     return $er->createQueryBuilder('c')
                         ->leftJoin('c.solution', 's')
                         ->where('s.id = :solution_id')
                         ->andWhere('c.deleted = 0')
                         ->setParameter('solution_id', $solutionTargets);
                 },
             ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-outline-success mb-2',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('solution');
        $resolver->setDefaults([
            'data_class' => Rule::class,
        ]);
    }
}
