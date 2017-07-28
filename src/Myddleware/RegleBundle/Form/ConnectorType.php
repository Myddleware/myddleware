<?php
namespace  Myddleware\RegleBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Myddleware\RegleBundle\Form\ConnectorParamType;
use Myddleware\RegleBundle\Entity\Connector;
use Myddleware\RegleBundle\Entity\ConnectorParam;

class ConnectorType extends AbstractType{
      
    private $_container;
    
    public function __construct($container) {
        $this->_container = $container;
        
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        //dump($options); die();
        $fieldsLogin = [];
        if( $options['data']->getSolution() !=null ){
            $fieldsLogin = $this->_container->get('myddleware_rule.' . $options['data']->getSolution()->getName())->getFieldsLogin();
            //Init ConnectorParams
            if(count($options['data']->getConnectorParams()) == 0){
                foreach ($fieldsLogin as $fieldLogin) {
                   $connectorParam = new ConnectorParam;
                   $connectorParam  ->setName($fieldLogin['name']);
                   $options['data']->addConnectorParam($connectorParam);
                }
            }
        }
        
        $builder->add('name', TextType::class,['label' => 'create_connector.connexion', 'attr' => ['id' => 'label','class' => 'params'] ]);
        $builder->add('connectorParams', CollectionType::class, array(
            'entry_type' => new ConnectorParamType($this->_container->getParameter('secret'), $fieldsLogin)
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
