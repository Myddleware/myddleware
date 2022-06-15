<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class ConnectorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $loginFields = $options['attr']['loginFields'] ?? null;
        $secret = $options['attr']['secret'] ?? null;
        $options['attr']['loginFields'] = null;
        if (null !== $options['data']->getSolution()) {
            // Init ConnectorParams
            if (empty($options['data']->getConnectorParams())) {
                foreach ($loginFields as $loginField) {
                    $connectorParam = new ConnectorParam();
                    $connectorParam->setName($loginField['name']);
                    $options['data']->addConnectorParam($connectorParam);
                }
            }
        }
        $builder->add('name', TextType::class, [
            'label' => 'create_connector.connection',
            'attr' => [
                'id' => 'label',
                'class' => 'params',
            ],
        ]);
        $builder->add('connectorParams', CollectionType::class, [
            'constraints' => new Valid(),
            'error_bubbling' => true,
            'entry_type' => ConnectorParamFormType::class,
            'entry_options' => [
                'attr' => [
                    'secret' => $secret,
                    'loginFields' => $loginFields,
                ],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Connector::class,
        ]);
    }
}
