<?php

namespace App\Form;

use App\Entity\Rule;
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
    private $rule;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('connectorSource', EntityType::class,[
                'class' => Connector::class,
                'choice_label'=> 'name',
                'label' => 'Connector source',
                //'required' => $options['require_due_date']
            ])
             ->add('connectorTarget', EntityType::class,[
                 'class' => Connector::class,
                 'choice_label'=> 'name',
                 'label' => 'Connector source',  
                 'query_builder' => function (EntityRepository $er) {
                    // return $er->createQueryBuilder('c')
                        // ->orderBy('c.name', 'ASC');
					return $er->createQueryBuilder('c')
						 ->leftJoin('c.solution', 's')
						 ->where('s.id = :solution_id')
						 ->setParameter('solution_id', 13);
					// return $er->findAllConnectorBySolution(13);

                        // dump( $er->createQueryBuilder('c')
                        // ->select('c.name')
                        // ->innerJoin('c.solution','solution')
                        // ->where('solution.id = 13')
                        // ->getQuery()
                        // ->getResult());  
                },
                 //'options' => $options['require_due_date']
             ])
            // ->add('connectorTarget', ('choices', ChoiceType::class, [
            // 'choice_attr' => ChoiceList::attr($this, function (?Category $category) {
            //     return $category ? ['data-uuid' => $category->getUuid()] : [];
            // })

            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-outline-success mb-2'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rule::class,
            //'require_due_date' => false,
        ]);
    }
}
