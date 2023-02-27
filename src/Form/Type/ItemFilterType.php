<?php
// ItemFilterType.php
namespace App\Form\Type;

use App\Entity\Rule;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use App\Repository\RuleRepository;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;
use Doctrine\ORM\EntityManagerInterface;


class ItemFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $builder
            ->add('filter', Filters\ChoiceFilterType::class, [               
                'choices' => [
                    '- Select your filters -' => 'default',
                    'Rule name' => 'ruleName',
                    'Date of modification' => 'dateModified',

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

                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('id', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',

                    'class' => 'form-control mt-2',
                    'id' => 'id',
                    'placeholder' => 'Id',
                ],
            ])
            ->add('dateCreated' , DateTimeType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'class' => 'form-control mt-2 calendar',
                    'id' => 'dateCreated',
                    'placeholder' => 'Date start',

                ],
            ])
            ->add('dateModified', DateTimeType::class, [
                'attr' => [
                    'hidden'=> 'hidden',

                    'placeholder' => 'Date modified',
                    'class' => 'form-control mt-2 calendar',
                    'id' => 'dateModified'
                ],
            ])
            ->add('moduleSource', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Module source',
                    'class' => 'form-control mt-2',
                    'id' => 'moduleSource'
                ],
            ])
            ->add('moduleTarget', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Module target',
                    'class' => 'form-control mt-2',
                    'id' => 'moduleTarget'
                ],
            ])
            ->add('ruleName',  Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findActiveRulesNames($entityManager),
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Name',
                    'class' => 'form-control mt-2',
                    'id' => 'name'
                ],
            ])
            ->add('nameSlug', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Name slug',
                    'class' => 'form-control mt-2',

                    'id' => 'nameSlug'
                ],
            ])
            ->add('connectorSource', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Connector source',
                    'class' => 'form-control mt-2',
                    'id' => 'connectorSource'
                ],
            ])
            ->add('connectorTarget', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Connector target',
                    'class' => 'form-control mt-2',
                    'id' => 'connectorTarget'
                ],
            ])
            ->add('createdBy', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Created by',
                    'class' => 'form-control mt-2',
                    'id' => 'createdBy'
                ],
            ])
            ->add('modifiedBy', TextType::class, [
                'attr' => [
                    'hidden'=> 'hidden',
                    'placeholder' => 'Modified by',
                    'class' => 'form-control mt-2',
                    'id' => 'modifiedBy'
                ],
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary mb-2',

                ],
            ]);
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

        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', EntityManagerInterface::class);
    }
}