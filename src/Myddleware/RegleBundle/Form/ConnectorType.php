<?php
namespace  Myddleware\RegleBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Myddleware\RegleBundle\Form\ConnectorParamType;
use Myddleware\RegleBundle\Entity\Connector;

class ConnectorType extends AbstractType{
    
 
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('name', TextType::class,['attr' => ['id' => 'label','class' => 'params'] ]);
        $builder->add('connectorParams', CollectionType::class, array(
            'entry_type' => ConnectorParamType::class
        ));
       
       /*foreach ($this->connectorParams['params'] as $name =>  $value) { 
                $builder->add($name, $value['type'],[
                    'data' => $value['value'],
                    'mapped' => false,
                    'attr' => [
                    'id' => 'param_'.$name,
                    'data-params' => $name,
                    'data-id' => $value['id'],
                    'class' => 'params'
                    ] 
                ]) ;
            
       }*/
              
   
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Connector::class
        ));
    }
    
}
