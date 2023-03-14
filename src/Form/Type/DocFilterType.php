<?php
namespace App\Form\Type;

use DateTime;
use App\Entity\Document;
use App\Manager\DocumentManager;
use App\Repository\DocumentRepository;
use App\Controller\DateIntervalDocType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;


class DocFilterType extends AbstractType
{

   

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['entityManager'];
        // $dateModified = new \DateTime('now');
        $builder
            ->add('status',Filters\ChoiceFilterType::class, [
                'choices'  => DocumentRepository::findStatusType($entityManager),
                'attr' => [
                    'class' => 'form-control mt-2',
                    'id' => 'status',
                    'placeholder' => 'status',
                ],
            ])
            ->add('globalStatus',Filters\ChoiceFilterType::class, [
                'choices'  => DocumentManager::lstGblStatus(),
                'attr' => [
                    'class' => 'form-control mt-2',
                    'id' => 'globalStatus',
                    'placeholder' => 'Global Status',
                ],
                'multiple' => true, // enable multiple choices
            ])
            ->add('source',  TextType::class, [
                //'choices'  => DocumentRepository::findModuleSource($entityManager),
                'attr' => [
                    'placeholder' => 'Source Id',
                    'class' => 'form-control mt-2',
                    'id' => 'source'
                ],
            ])
            ->add('target',  TextType::class, [
                // 'choices'  => RuleRepository::findModuleSource($entityManager),
                'attr' => [
                    'placeholder' => 'Target Id',
                    'class' => 'form-control mt-2',
                    'id' => 'target'
                ],
            ])
            ->add('type',Filters\ChoiceFilterType::class, [
                'choices'  => DocumentManager::lstType(),
                'attr' => [
                    'placeholder' => 'Name slug',
                    'class' => 'form-control mt-2',
                    'id' => 'type'
                ],
            ])
            ->add('date_modif_start', DateTimeType::class, [
                'required' => false,
                // 'widget' => 'choice',
                // 'html5' => false,
                // 'format' => 'yyyy-MM-dd HH:mm:ss',
                'attr' => [
                    'placeholder' => 'Date of modification Start',
                    'class' => 'form-control mt-2',
                    'id' => 'date_modif_start'
                ],
                // 'data' => new \DateTime()
            ])
            ->add('date_modif_end', DateTimeType::class, [
                'required' => false,
                // 'widget' => 'choice',
                // 'html5' => false,
                // 'format' => 'yyyy-MM-dd HH:mm:ss',
                'attr' => [
                    'placeholder' => 'Date of modification End',
                    'class' => 'form-control mt-2',
                    'id' => 'date_modif_end'
                ],
                // 'data' => new \DateTime()
            ]);


           
    }

    public function getBlockPrefix()
    {
        return 'item_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'validation_groups' => array('filtering'),
            'entityManager' => null,
            'method' => 'GET',
            ));

        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', EntityManagerInterface::class);
    }
}