<?php

namespace App\Form;

use App\Entity\Connector;
use App\Entity\ConnectorParam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConnectorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fieldsLogin = $options['attr']['fieldsLogin'] ?? null;
        $secret = $options['attr']['secret'] ?? null;
        $options['attr']['fieldsLogin'] = null;
        if (null != $options['data']->getSolution()) {
            //Init ConnectorParams
            if (empty($options['data']->getConnectorParams())) {
                foreach ($fieldsLogin as $fieldLogin) {
                    $connectorParam = new ConnectorParam();
                    $connectorParam->setName($fieldLogin['name']);
                    $options['data']->addConnectorParam($connectorParam);
                }
            }
        }

        $builder->add('name', TextType::class, [
            'label' => 'create_connector.connexion', 
            'attr' => ['id' => 'label', 'class' => 'params'],
            'constraints' => [new NotBlank(['message' => 'Connector name is required'])]
        ]);
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Connector::class,
        ]);
    }
}
