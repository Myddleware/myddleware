<?php
namespace Myddleware\RegleBundle\Service;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Manage the session of myddleware ( Refactoring since the Controllers )
 * @author Dolyveen Renault <drenault@karudev-informatique.fr>
 */
class SessionService{
    
    CONST MYDDLEWARE_SESSION_INDEX = 'myddlewareSession';
    private $_session;
    
    public function __construct(Session $session) {
        $this->_session = $session;
    }
    
    public function getMyddlewareSession()
    {
        if(!$this->_session->has(self::MYDDLEWARE_SESSION_INDEX)){
            $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,[]);
        }
        return $this->_session->get(self::MYDDLEWARE_SESSION_INDEX);
    }
        
    ############# SOLUTION ###################
    
    public function setSolutionName($solutionName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['solution']['callback'] = $solutionName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }

    public function getSolutionName()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['connector']['solution']['callback'];
    }
    
    public function setSolutionType()
    {
        return null;
    }
    
    public function getSolutionType($type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['connector']['solution'][$type];
    }
    
    ############# SOLUTION ###################
    
    
    ############# SOURCE ###################
    
    public function getSource()
    {
        return null;
    }
    
    public function setSource()
    {
        return null;
    }
    
    public function getRefreshTokenSource()
    {
        return null;
    }
    
    public function setRefreshTokenSource()
    {
        return null;
    }
    
    public function getTokenSource()
    {
        return null;
    }
    
    public function setTokenSource()
    {
        return null;
    }
    
    ############# SOURCE ###################
    
    ############# UPLOAD ###################
    
    public function getUploadName()
    {
        return null;
    }
    
    public function setUploadName()
    {
        return null;
    }
    
    public function getUploadError()
    {
        return null;
    }
    
    public function setUploadError()
    {
        return null;
    }
    
    public function removeUpload()
    {
        return null;
    }
    
    ############# UPLOAD ###################
    
    ############# CONNECTOR ###################
    
    public function getConnectorAnimation()
    {
        return null;
    }
    
    public function setConnectorAnimation()
    {
        return null;
    }
      
    public function setConnectorAddType()
    {
        return null;
    }
    
    public function getConnectorAddType()
    {
        return null;
    }
    
    public function setConnectorAddMessage()
    {
        return null;
    }
    
    public function getConnectorAddMessage()
    {
        return null;
    }
    
    public function setConnectorValues()
    {
        return null;
    }
    
    public function getConnectorValues()
    {
        return null;
    }
    
    public function removeConnector()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['connector']); #L391 in ConnectorController
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeConnectorAdd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['connector']['add']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeConnectorValues()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['connector']['values']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    ############# CONNECTOR ###################
    
    ############# RULE ###################
    
    public function isParamRuleExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['params']['rule']); 
    }
    
    public function setParamRuleNameValid($isValid)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['rulename_valide'] = $isValid;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleNameValid()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['rulename_valide'];
    }
    
    public function setParamRuleName($ruleName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['rulename'] = $ruleName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleName()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['rulename'];
    }
    
    public function setParamRuleConnectorSourceId($connectorSouceId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['connector']['source'] = $connectorSouceId;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleConnectorSourceId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['connector']['source'];
    }
    
    public function setParamRuleConnectorCibleId($connectorCibleId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['connector']['cible'] = $connectorCibleId;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleConnectorCibleId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['connector']['cible'];
    }
    
    
    public function setParamRuleLastId($id)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['last_version_id'] = $id;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleLastId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['last_version_id'];
    }
    
    
    public function setParamRuleSourceSolution($solutionName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['source']['solution'] = $solutionName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceSolution()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['source']['solution'];
    }
    
    public function getParamRuleSource()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['source'];
    }
    
    
    public function setParamRuleSourceConnector($connectorName, $connectorValue)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['source'][$connectorName] = $connectorValue;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceConnector($connectorName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['source'][$connectorName];
    }
    
    
    public function setParamRuleCibleSolution($solutionName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['cible']['solution'] = $solutionName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleSolution()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['cible']['solution'];
    }
    
    public function setParamRuleCibleConnector($connectorName, $connectorValue)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['cible'][$connectorName] = $connectorValue;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleConnector($connectorName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['cible'][$connectorName];
    }

    public function setParamRuleReloadParams($params)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['reload']['params'] = json_encode($params);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleReloadParams()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule']['reload']['params']);
    } 
    
    public function setParamRuleReloadFields($fields)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['reload']['fields'] = json_encode($fields);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleReloadFields()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule']['reload']['fields']);
    }

    public function setParamRuleReloadFilter($filter)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['reload']['filter'] = json_encode($filter);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleReloadFilter()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule']['reload']['filter']);
    }        
    
    public function setParamRuleSourceModule($moduleSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['source']['module'] = $moduleSource;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    
    public function getParamRuleSourceModule()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['source']['module'];
    }

    public function setParamRuleCibleModule($moduleTarget)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['cible']['module'] = $moduleTarget;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleModule()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['cible']['module'];
    }     
  
    public function setRuleId($id)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['rule']['newid'] = $id;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getRuleId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['rule']['newid'];
    }
    
    public function removeRuleId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['rule']['newid']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    ############# RULE ###################
    
    
    ############# FLUX FILTER ###################
    
    public function setFluxFilterWhere($where)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['where'] = $where;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterWhere()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['where'];
    }
    
    
    public function setFluxFilterRuleName($ruleName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['rule'] = $ruleName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterRuleName()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['rule']['c']['rule'];
    }
    
    ############# FLUX FILTER ###################
    
    
}