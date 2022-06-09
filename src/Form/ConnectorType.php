<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use App\Form\DataTransformer\ConnectorParamsValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectorType extends AbstractType
{
    // private $transformer;

    // public function __construct(ConnectorParamsValueTransformer $transformer)
    // {
    //     $this->transformer = $transformer;
    // }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $builder
        //     ->add('connectorParams', CollectionType::class, [
        //         'entry_type' => ConnectorParamFormType::class,
        //         'entry_options' => ['label' => false],
        //         'row_attr' => ['class' => 'p-3'],
        //     ]);
        // $builder->get('connectorParams')
        //         ->addModelTransformer($this->transformer);

        // $builder->addEventListener(
        //     FormEvents::POST_SET_DATA,
        //     [$this, 'onPostSetData']
        // );

        $loginFields = isset($options['attr']['loginFields']) ? $options['attr']['loginFields'] : null;
        $secret = isset($options['attr']['secret']) ? $options['attr']['secret'] : null;
        $options['attr']['loginFields'] = null;
        if (null != $options['data']->getSolution()) {
            // Init ConnectorParams
            if (empty($options['data']->getConnectorParams())) {
                foreach ($loginFields as $loginField) {
                    $connectorParam = new ConnectorParam();
                    $connectorParam->setName($loginField['name']);
                    $options['data']->addConnectorParam($connectorParam);
                }
            }
        }

        $builder->add('name', TextType::class, ['label' => 'create_connector.connection', 'attr' => ['id' => 'label', 'class' => 'params']]);
        $builder->add('connectorParams', CollectionType::class, [
            'constraints' => new Valid(),
            'error_bubbling' => true,
            'entry_type' => ConnectorParamType::class,
            'entry_options' => [
                'attr' => [
                    'secret' => $secret,
                    'fieldsLogin' => $fieldsLogin,
                ],
            ],
        ]);
    }

    // public function onPostSetData(FormEvent $event): void
    // {
    //     $form = $event->getForm();
    //     $data = $event->getData();
    //     if ($data) {
    //         if ($data instanceof Connector) {
    //             if (null === $data->getName()) {
    //                 return;
    //             }
    //         }
    //         // $form->remove('connectorParams');
    //     }
    // }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Connector::class,
        ]);
    }
}
