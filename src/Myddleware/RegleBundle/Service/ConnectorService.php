<?php

namespace Myddleware\RegleBundle\Service;

use Myddleware\RegleBundle\Entity\Connector;
use Symfony\Component\DependencyInjection\Container;

class ConnectorService {

    private $_container;

    public function __construct(Container $container) {
        $this->_container = $container;
    }

    /**
     * @author Dolyveen Renault <d.renault@karudev-informatique.fr>
     * @param Connector $connector
     * @return array 
     */
    public function getConnectorParamFormatted(Connector $connector) {

        $connectorP = $connector->getConnectorParams();

        if (!$connector) {
            return $this->redirect($this->generateUrl('regle_connector_list'));
        }

        if (isset($connectorP) && count($connectorP) > 0) {
            $connectorParams = array();
            $connectorParams['label'] = $connector->getName();

            $connectorParams['solution']['name'] = $connector->getSolution()->getName();
            $connectorParams['solution']['id'] = $connector->getSolution()->getId();
            foreach ($connectorP as $connectorObj) {
                $connectorParams['id'] = $connectorObj->getConnector()->getId();
                $connectorParams['params'][$connectorObj->getName()]['value'] = $this->decrypt_params($connectorObj->getValue());
                $connectorParams['params'][$connectorObj->getName()]['id'] = $connectorObj->getId();
            }
        }

        $solution = $this->_container->get('myddleware_rule.' . $connectorParams['solution']['name']);

        foreach ($solution->getFieldsLogin() as $k => $v) {

            $connectorParams['params'][$v['name']]['type'] = $v['type'];
        }

        return $connectorParams;
    }

    // Décrypte les paramètres de connexion d'une solution
    private function decrypt_params($tab_params) {
        // Instanciate object to decrypte data
        $encrypter = new \Illuminate\Encryption\Encrypter(substr($this->_container->getParameter('secret'), -16));
        if (is_array($tab_params)) {
            $return_params = array();
            foreach ($tab_params as $key => $value) {
                if (is_string($value)) {
                    $return_params[$key] = $encrypter->decrypt($value);
                }
            }
            return $return_params;
        } else {
            return $encrypter->decrypt($tab_params);
        }
    }

}
