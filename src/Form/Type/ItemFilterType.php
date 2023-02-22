<?php
// ItemFilterType.php
namespace App\Form\Type;

use App\Entity\Rule;
use DateTime;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class ItemFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filter', Filters\ChoiceFilterType::class, [               
                'choices' => [
                    'Name' => 'name',
                    'Date of modification' => 'dateModified',
                    'Statut' => 'statut',
                    'Document content' => 'doc_content',
                    'Id' => 'id',
                    'Date start' => 'dateCreated',
                    'Module source' => 'moduleSource',
                    'Module target' => 'moduleTarget',
                    'Name slug' => 'nameSlug',
                    'Connector source' => 'connectorSource',
                    'Connector target' => 'connectorTarget',
                    'Created by' => 'createdBy',
                    'Modified by' => 'modifiedBy',
                ],
            ])
            ->add('id', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'id'
                ],
            ])
            ->add('dateCreated' , DateTimeType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'dateCreated'
                ],
            ])
            ->add('dateModified', DateTimeType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'dateModified'
                ],
            ])
            ->add('moduleSource', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'moduleSource'
                ],
            ])
            ->add('moduleTarget', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'moduleTarget'
                ],
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'name'
                ],
            ])
            ->add('nameSlug', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'nameSlug'
                ],
            ])
            ->add('connectorSource', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'connectorSource'
                ],
            ])
            ->add('connectorTarget', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'connectorTarget'
                ],
            ])
            ->add('createdBy', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'createdBy'
                ],
            ])
            ->add('modifiedBy', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'id' => 'modifiedBy'
                ],
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-outline-success mb-2',
                ],
            ]);
            // $builder->add('options', Filters\CollectionAdapterFilterType::class, array(
               
            //     'add_shared' => function (FilterBuilderExecuterInterface $qbe)  {
            //         $closure = function (QueryBuilder $filterBuilder, $alias, $joinAlias, Expr $expr) {
            //             // add the join clause to the doctrine query builder
            //             // the where clause for the label and color fields will be added automatically with the right alias later by the Lexik\Filter\QueryBuilderUpdater
            //             $filterBuilder->leftJoin($alias . '.options', $joinAlias);
            //         };
    
            //         // then use the query builder executor to define the join and its alias.
            //         $qbe->addOnce($qbe->getAlias().'.options', 'opt', $closure);
            //     },
            // ));
    }

    public function getBlockPrefix()
    {
        return 'item_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering'), // avoid NotBlank() constraint-related message
            'data_class' => Rule::class,
        ));
    }
}