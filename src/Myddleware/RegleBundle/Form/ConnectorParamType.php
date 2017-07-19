<?php
namespace  Myddleware\RegleBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Myddleware\RegleBundle\Entity\ConnectorParam;

class ConnectorParamType extends AbstractType{
    
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
       $builder->add('name');
       $builder->add('value');
              
   
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
