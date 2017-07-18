<?php
namespace  Myddleware\RegleBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ConnectorType extends AbstractType{
    
    private $connectorParams;
    
    public function __construct(array $connectorParams = null) {
        $this->connectorParams = $connectorParams;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
       $builder->add('name', TextType::class,['attr' => ['id' => 'label','class' => 'params'] ]);
       
       foreach ($this->connectorParams['params'] as $name =>  $value) { 
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
            
       }
              
   
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Myddleware\RegleBundle\Entity\Connector'
        ));
    }
    
}
