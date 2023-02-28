<?php
// ItemFilterType.php
namespace App\Form\Type;

use App\Entity\Rule;
use App\Entity\Document;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Doctrine\ORM\Mapping\Entity;
use App\Repository\RuleRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderExecuterInterface;


class ItemFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $builder
            ->add('id', TextType::class, [
                'attr' => [
                    'hidden'=> 'true',
                    'class' => 'form-control mt-2',
                    'id' => 'id',
                    'placeholder' => 'Id',
                ],
            ])
            ->add('dateCreated', DateTimeType::class, [
                // 'widget' => 'single_text',
                'attr' => [
                    'hidden'=> 'true',
                    'class' => 'form-control mt-2 ',
                    'id' => 'dateCreated',
                    'placeholder' => 'Date start',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            
            ->add('dateModified', DateTimeType::class, [
                // 'widget' => 'single_text',
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Date modified',
                    'class' => 'form-control mt-2',
                    'id' => 'dateModified'
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('moduleSource', Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findModuleSource($entityManager),
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Module source',
                    'class' => 'form-control mt-2',
                    'id' => 'moduleSource'
                ],
            ])
            ->add('moduleTarget', Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findModuleTarget($entityManager),
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Module target',
                    'class' => 'form-control mt-2',
                    'id' => 'moduleTarget'
                ],
            ])
            ->add('name',  Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findActiveRulesNames($entityManager),
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Name',
                    'class' => 'form-control mt-2',
                    'id' => 'name'
                ],
            ])
            ->add('nameSlug',Filters\ChoiceFilterType::class, [
                'choices'  => RuleRepository::findNameSlug($entityManager),
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Name slug',
                    'class' => 'form-control mt-2',
                    'id' => 'nameSlug'
                ],
            ])
            ->add('connectorSource', TextType::class, [
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Connector source',
                    'class' => 'form-control mt-2',
                    'id' => 'connectorSource'
                ],
            ])
            ->add('connectorTarget', TextType::class, [
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Connector target',
                    'class' => 'form-control mt-2',
                    'id' => 'connectorTarget'
                ],
            ])
            ->add('createdBy', TextType::class, [
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Created by',
                    'class' => 'form-control mt-2',
                    'id' => 'createdBy'
                ],
            ])
            ->add('modifiedBy', TextType::class, [
                'attr' => [
                    'hidden'=> 'true',
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