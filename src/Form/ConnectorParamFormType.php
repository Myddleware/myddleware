<?php

declare(strict_types=1);

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

class ConnectorParamFormType extends AbstractType
{
    private ConnectorParamsValueTransformer $transformer;

    private array $solutionLoginFields;

    public function __construct(
        ConnectorParamsValueTransformer $transformer,
        array $solutionLoginFields = [],
        ?string $secret = null,
    ) {
        $this->transformer = $transformer;
        $this->solutionLoginFields = $solutionLoginFields;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->solutionLoginFields = $options['attr']['loginFields'] ?? null;

        $builder
            ->add('value', TextType::class, [
                'label' => 'Value',
                'empty_data' => '',
                'error_bubbling' => true,
                'row_attr' => ['data-controller' => 'solution'],
                'mapped' => false,
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
            'inherit_data' => true,
        ]);
    }

    public function onPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();
        $type = TextType::class;
        if ($data) {
            if ($data instanceof ConnectorParam) {
                if ('' === $data->getName()) {
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

            foreach ($this->solutionLoginFields as $loginField) {
                if ($loginField['name'] === $data->getName()) {
                    $type = $loginField['type'];
                    $option['label'] = 'solution.fields.'.$loginField['name'];
                    if ('Symfony\Component\Form\Extension\Core\Type\PasswordType' == $type) {
                        $option['attr']['autocomplete'] = 'off';
                        $option['attr']['value'] = $data->getValue(); // Force value of the password
                    }
                }
            }
            $form->add('value', $type, $option);
            if (null === $data->getValue()) {
                $form->add('name', HiddenType::class, ['data' => $name]);
            }
        }
    }
}
