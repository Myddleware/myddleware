<?php
namespace  Myddleware\RegleBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Myddleware\RegleBundle\Entity\ConnectorParam;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Myddleware\RegleBundle\Form\DataTransformer\ConnectorParamsValueTransformer;


class ConnectorParamType extends AbstractType{
    
    private $_secret;
    private $_solutionFieldsLogin;
    
    public function __construct($secret, $solutionFieldsLogin) {
        $this->_secret = $secret;
        $this->_solutionFieldsLogin = $solutionFieldsLogin;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('value')->addModelTransformer(new ConnectorParamsValueTransformer($this->_secret));
       
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
        $connectorParam = $event->getData();
        $form = $event->getForm();
 
        $type = TextType::class;
        $option = [];
        
        foreach ($this->_solutionFieldsLogin as $f){
            if($f['name'] == $connectorParam->getName()){
               $type = $f['type'];
               $option['label'] = $f['label'];
            }
        }
         
        $form->add('value', $type, $option);
      
        
    });
               
   
    }
    
  
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ConnectorParam::class
        ));
    }
    
}
