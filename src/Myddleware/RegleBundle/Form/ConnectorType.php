<?php
namespace  Myddleware\RegleBundle\Form;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Myddleware\RegleBundle\Form\ConnectorParamType;
use Myddleware\RegleBundle\Entity\Connector;
use Myddleware\RegleBundle\Entity\ConnectorParam;
use Symfony\Component\Validator\Constraints\Valid;


class ConnectorType extends AbstractType{


    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $fieldsLogin = isset($options['attr']['fieldsLogin']) ? $options['attr']['fieldsLogin'] : null;
        $secret = isset($options['attr']['secret']) ? $options['attr']['secret'] : null;
        $options['attr']['fieldsLogin'] = null;
        if( $options['data']->getSolution() !=null ){
            //Init ConnectorParams	
            if(empty($options['data']->getConnectorParams())){
                foreach ($fieldsLogin as $fieldLogin) {
                   $connectorParam = new ConnectorParam;
                   $connectorParam  ->setName($fieldLogin['name']);
                   $options['data']->addConnectorParam($connectorParam);
                }
            }
        }
        
        $builder->add('name', TextType::class,['label' => 'create_connector.connexion', 'attr' => ['id' => 'label','class' => 'params'] ]);
        $builder->add('connectorParams', CollectionType::class, array(
            'constraints' => new Valid(),
            'error_bubbling' => true,
//            'entry_type' => new ConnectorParamType($this->_container->getParameter('secret'), $fieldsLogin),
            'entry_type' =>  ConnectorParamType::class,
            'entry_options' => array(
                'attr' => array(
                        'secret' => $secret,
                        'fieldsLogin' => $fieldsLogin
                )
        )
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
            'data_class' => Connector::class,
            //'container'  => null
        ));
    }
    
}
