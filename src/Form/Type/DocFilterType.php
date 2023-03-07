<?php
// ItemFilterType.php
namespace App\Form\Type;

use App\Entity\Rule;
use App\Entity\Document;
use App\Repository\DocumentRepository;
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

class DocFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        $builder
            ->add('status',Filters\ChoiceFilterType::class, [
                'choices'  => DocumentRepository::findStatusType($entityManager),
                'attr' => [
                    'hidden'=> 'true',
                    'class' => 'form-control mt-2',
                    'id' => 'status',
                    'placeholder' => 'status',
                ],
            ])
            ->add('source',  TextType::class, [
                //'choices'  => DocumentRepository::findModuleSource($entityManager),
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Module source',
                    'class' => 'form-control mt-2',
                    'id' => 'source_id'
                ],
            ])
            ->add('target',  TextType::class, [
                // 'choices'  => RuleRepository::findModuleSource($entityManager),
                'attr' => [
                    'hidden' => 'true',
                    'placeholder' => 'Module source',
                    'class' => 'form-control mt-2',
                    'id' => 'target_id'
                ],
            ])
            //     ->add('datas',  TextType::class, [
            //         //'choices'  => DocumentRepository::findModuleSource($entityManager),
            //         'attr' => [
            //             'hidden'=> 'true',
            //             'placeholder' => 'Source content',
            //             'class' => 'form-control mt-2',
            //             'id' => 'datas'
            //     ],
            // ])
                ->add('sourceDateModified',  DateTimeType::class, [
                    //'choices'  => DocumentRepository::findModuleSource($entityManager),
                    'attr' => [
                        'hidden'=> 'true',
                        'placeholder' => 'Source content',
                        'class' => 'form-control mt-2',
                        'id' => 'sourceDateModified'
                ],
            ])
            ->add('type',Filters\ChoiceFilterType::class, [
                'choices'  => DocumentRepository::findDocType($entityManager),
                'attr' => [
                    'hidden'=> 'true',
                    'placeholder' => 'Name slug',
                    'class' => 'form-control mt-2',
                    'id' => 'type'
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
            'data_class' => Document::class,
        ));

        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', EntityManagerInterface::class);
    }
}