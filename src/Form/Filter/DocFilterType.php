<?php
namespace App\Form\Filter;

use App\Manager\DocumentManager;
use App\Repository\DocumentRepository;
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
        $builder
            ->add('status',Filters\ChoiceFilterType::class, [
                'choices'  => DocumentManager::lstStatus(),
                'attr' => [
                    'class' => 'form-control mt-2',
                    'id' => 'status',
                    'placeholder' => 'Status',
                ],
            ])
            ->add('globalStatus',Filters\ChoiceFilterType::class, [
                'choices'  => DocumentManager::lstGblStatus(),
                'attr' => [
                    'class' => 'form-control mt-2 select2',
                    'id' => 'globalStatus',
                    'placeholder' => 'Global Status',
                ],
                'multiple' => true, // enable multiple choices
            ])
            ->add('sourceId',  TextType::class, [
                'attr' => [
                    'placeholder' => 'Source Id',
                    'class' => 'form-control mt-2',
                    'id' => 'source'
                ],
            ])
            ->add('target',  TextType::class, [
                'attr' => [
                    'placeholder' => 'Target Id',
                    'class' => 'form-control mt-2',
                    'id' => 'target'
                ],
            ])
            ->add('sort_field',  TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'invisible sort',
                    'class' => 'form-control mt-2',
                    'id' => 'sort_field'
                ],
            ])
            ->add('sort_order',  TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'invisible sort',
                    'class' => 'form-control mt-2',
                    'id' => 'sort_order'
                ],
            ])
            ->add('reference',  TextType::class, [
                'attr' => [
                    'placeholder' => 'Reference',
                    'class' => 'form-control mt-2',
                    'id' => 'reference'
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
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Date of modification Start',
                    'class' => 'form-control mt-2 calendar',
                    'id' => 'date_modif_start'
                ],
            ])
            ->add('date_modif_end', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Date of modification End',
                    'class' => 'form-control mt-2 calendar',
                    'id' => 'date_modif_end'
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
            'csrf_protection' => false,
            'validation_groups' => array('filtering'),
            'entityManager' => null,
            'method' => 'GET',
            ));

        $resolver->setRequired('entityManager');
        $resolver->setAllowedTypes('entityManager', EntityManagerInterface::class);
    }
}