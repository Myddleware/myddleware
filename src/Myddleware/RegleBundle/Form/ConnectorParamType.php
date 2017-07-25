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
    
    public function __construct($secret) {
        $this->_secret = $secret;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
       $builder->add('value')->addModelTransformer(new ConnectorParamsValueTransformer($this->_secret));
               
   
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
