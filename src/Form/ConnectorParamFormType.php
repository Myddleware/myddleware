<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ConnectorParam;
use App\Form\DataTransformer\ConnectorParamsValueTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectorParamFormType extends AbstractType
{
    private $transformer;

    public function __construct(ConnectorParamsValueTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', EntityType::class, [
                'label' => 'Name',
                'class' => ConnectorParam::class,
                'choice_label' => 'name',
                'empty_data' => '',
                'attr' => ['data-controller' => 'solution'],
            ])
            ->add('value', TextType::class, [
                'label' => 'Value',
                'empty_data' => '',
                'row_attr' => ['data-controller' => 'solution'],
            ]);
        $builder->get('value')
            ->addModelTransformer($this->transformer);

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$this, 'onPostSetData']
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConnectorParam::class,
        ]);
    }

    public function onPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data) {
            if ($data instanceof ConnectorParam) {
                if (null === $data->getName()) {
                    return;
                }
            }
            // $form->remove('name');
        }
    }
}
