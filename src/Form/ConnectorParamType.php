<?php

namespace App\Form;

use App\Entity\ConnectorParam;
use App\Form\DataTransformer\ConnectorParamsValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConnectorParamType extends AbstractType
{
    private $_secret;
    private $_solutionFieldsLogin;
    private ConnectorParamsValueTransformer $connectorParamsValueTransformer;

    public function __construct(ConnectorParamsValueTransformer $connectorParamsValueTransformer)
    {
        $this->connectorParamsValueTransformer = $connectorParamsValueTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->_secret = $options['attr']['secret'] ?? null;
        $this->_solutionFieldsLogin = $options['attr']['fieldsLogin'] ?? null;

        $builder->add('value', TextType::class, ['error_bubbling' => true])->addModelTransformer($this->connectorParamsValueTransformer);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $connectorParam = $event->getData();
            $form = $event->getForm();
            $type = TextType::class;
            $option['attr']['class'] = 'params';

            $id = $connectorParam->getId();
            $name = $connectorParam->getName();
            $option['attr']['data-param'] = $name;

            if ('wsdl' == $name or 'file' == $name) {
                $option['attr']['readonly'] = 'readonly';
                $option['attr']['data-id'] = $id;
                $option['attr']['placeholder'] = 'create_connector.upload_placeholder';
            }

            foreach ($this->_solutionFieldsLogin as $f) {
                $option['required'] = false;
                if ($f['name'] == $connectorParam->getName()) {
                    $type = $f['type'];
                    $option['label'] = 'solution.fields.'.$f['name'];
                    if ('Symfony\Component\Form\Extension\Core\Type\PasswordType' == $type) {
                        $option['attr']['autocomplete'] = 'off';
                    }
                }
            }
            $form->add('value', $type, $option);
            if (null == $connectorParam->getValue()) {
                $form->add('name', HiddenType::class, ['data' => $name]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ConnectorParam::class,
        ]);
    }
}
