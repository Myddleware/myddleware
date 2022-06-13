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
    private $_secret;
    private $_solutionLoginFields;

    public function __construct(ConnectorParamsValueTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->_secret = $options['attr']['secret'] ?? null;
        $this->_solutionLoginFields = $options['attr']['loginFields'] ?? null;
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
                'error_bubbling' => true,
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
            $option['attr']['class'] = 'params';

            $id = $data->getId();
            $name = $data->getName();
            $option['attr']['data-param'] = $name;

            if ('wsdl' == $name or 'file' == $name) {
                $option['attr']['readonly'] = 'readonly';
                $option['attr']['data-id'] = $id;
                $option['attr']['placeholder'] = 'create_connector.upload_placeholder';
            }

            foreach ($this->_solutionLoginFields as $loginField) {
                if ($loginField['name'] == $data->getName()) {
                    $type = $loginField['type'];
                    $option['label'] = 'solution.fields.' . $loginField['name'];
                    if ('Symfony\Component\Form\Extension\Core\Type\PasswordType' == $type) {
                        $option['attr']['autocomplete'] = 'off';
                        $option['attr']['value'] = $data->getValue(); // Force value of the password
                    }
                }
            }

            if (null === $data->getValue()) {
                $form->add('name', HiddenType::class, ['data' => $name]);
            }
            // $form->remove('name');
        }
    }
}
