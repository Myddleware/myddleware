<?php
namespace  Myddleware\RegleBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Myddleware\RegleBundle\Entity\ConnectorParam;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Myddleware\RegleBundle\Form\DataTransformer\ConnectorParamsValueTransformer;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class ConnectorParamType extends AbstractType{
    
    private $_secret;
    private $_solutionFieldsLogin;
    

    
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $this->_secret = isset($options['attr']['secret']) ? $options['attr']['secret'] : null;
        $this->_solutionFieldsLogin = isset($options['attr']['fieldsLogin']) ? $options['attr']['fieldsLogin'] : null;

      
        $builder->add('value',TextType::class,['error_bubbling' => true])->addModelTransformer(new ConnectorParamsValueTransformer($this->_secret));
       
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
			$connectorParam = $event->getData();
			$form = $event->getForm();
			$type = TextType::class;
			$option['attr']['class'] = 'params';
			
			$id = $connectorParam->getId();
			$name = $connectorParam->getName();
			$option['attr']['data-param'] = $name;
			 
			if ($name == 'wsdl' or $name == 'file') {
			   // $option['id'] = 'param_' . $name;
				$option['attr']['readonly'] = 'readonly';
				$option['attr']['data-id'] = $id;
				$option['attr']['placeholder'] = 'create_connector.upload_placeholder';
			}

			foreach ($this->_solutionFieldsLogin as $f){
				if($f['name'] == $connectorParam->getName()){         
				   $type = $f['type'];
				   $option['label'] = 'solution.fields.'.$f['name'];
				   if($type == 'Symfony\Component\Form\Extension\Core\Type\PasswordType'){
					   $option['attr']['autocomplete'] = 'off'; 
					   $option['attr']['value'] = $connectorParam->getValue(); // Force value of the password
				   }
				}
			}	
			$form->add('value', $type, $option);
			if($connectorParam->getValue() == null){
				$form->add('name', HiddenType::class, ['data' => $name]);
			} 
		});
    }
  
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ConnectorParam::class,
//            'secret' => null,
//            'fieldsLogin' => null
        ));
    }
    
}
