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
    
    
    CONST ERROR_CREATE_RULE_INDEX = 'create_rule';
    
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
    
    public function isSolutionNameExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['myddleware']['connector']['solution']['callback']);
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
    
    
    ############# UPLOAD ###################
    
    public function getUploadName()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['upload']['name'];
    }
    
    public function setUploadName($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['upload']['name'] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeUpload()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['upload']); 
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getUploadError()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['upload']['error'];
    }
    
    public function setUploadError($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['upload']['error'] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }

    public function isUploadNameExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['myddleware']['upload']['name']);
    }
    
    public function isUploadErrorExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['myddleware']['upload']['error']);
    }
    
    ############# UPLOAD ###################
    
    ############# CONNECTOR ###################
    
    public function isConnectorExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['myddleware']['connector']);
    }
    
    public function getConnectorAnimation()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['connector']['animation'];
    }
    
    public function setConnectorAnimation($bool)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['animation'] = $bool;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
      
    public function setParamConnectorAddType($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['add']['type'] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamConnectorAddType()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['connector']['add']['type'];
    }
    
    public function setConnectorAddMessage($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['add']['message'] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getConnectorAddMessage()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['connector']['add']['message'];
        
    }
    
    public function setConnectorValues()
    {
        return null;
    }
    
    public function getConnectorValues()
    {
        return null;
    }
    
    public function setParamConnectorSource($source)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['connector']['source'] = $source;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }
    
    public function getParamConnectorSource()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['connector']['source'];
    }
    
    public function getParamConnectorSourceSolution()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['connector']['source']['solution'];
    }
    
    public function isParamConnectorSourceExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['connector']['source']);
    }
    
    public function isParamRuleSourceModuleExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['source']['module']);
    }
    
    public function isParamRuleCibleModuleExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['cible']['module']);
    }
    
    public function isParamRuleCibleModeExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['cible']['mode']);
    }
    
    public function setParamConnectorSourceToken($token)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['connector']['source']['token'] = $token;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }
    
    public function getParamConnectorSourceToken()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['connector']['source']['token']);
    }
    
    public function isParamConnectorSourceTokenExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['connector']['source']['token']);
    }
    
    public function setParamConnectorSourceRefreshToken($refreshToken)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['connector']['source']['refreshToken'] = $refreshToken;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }
    
    public function getParamConnectorSourceRefreshToken()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['connector']['source']['refreshToken']);
    }
    
    public function isParamConnectorSourceRefreshTokenExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['connector']['source']['refreshToken']);
    }
    
      
    public function setParamConnectorSolutionSource($key, $source)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector'][$key]['solution']['source'] = json_encode($source);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamConnectorSolutionSource($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['myddleware']['connector'][$key]['solution']['source']);
    }
    
    
     public function setParamConnectorSolutionTarget($key, $target)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector'][$key]['solution']['target'] = json_encode($target);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamConnectorSolutionTarget($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['myddleware']['connector'][$key]['solution']['target']);
    }
    
    
    public function setParamConnectorParentType($parent, $type, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['connector'][$parent][$type] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamConnectorParentType($parent, $type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['connector'][$parent][$type];
    }
    
    
    public function isParamConnectorExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['connector']);
    }
    
    public function removeConnector()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['connector']); #L391 in ConnectorController
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
     public function removeMyddlewareConnector()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['connector']); 
        
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
    
     public function getParamConnectorValues()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['myddleware']['connector']['values']);
    }
    
    public function setParamConnectorValues($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['values'] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function isConnectorValuesExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['myddleware']['connector']['values']);
    }
    
    ############# CONNECTOR ###################
    
    ############# MAILCHIMP ###################
    
    public function setMailchimpParamConnexion($redirectUri, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['mailchimp'][$redirectUri]['paramConnexion'] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getMailchimpParamConnexion($redirectUri)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['myddleware']['connector']['mailchimp'][$redirectUri]['paramConnexion'];
    }
    
    ############# MAILCHIMP ###################
    
    ############# RULE ###################
    
    public function removeParamRule($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['rule'][$key]);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeParamParentRule($key, $parent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['rule'][$key][$parent]);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function setParamParentRule($key, $parent, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key][$parent] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamParentRule($key, $parent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key][$parent];
    }
    
     
    public function setParamRuleConnectorParent($key, $parent, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['connector'][$parent] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleConnectorParent($key, $parent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['connector'][$parent];
    }
    
    public function setParamRuleParentName($key, $parent, $name, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key][$parent][$name] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleParentName($key, $parent, $name)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key][$parent][$name];
    }
    
    public function isParamRuleExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]); 
    }
    
    public function setParamRuleNameValid($key, $isValid)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['rulename_valide'] = $isValid;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleNameValid($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return (bool)$myddlewareSession['param']['rule'][$key]['rulename_valide'];
    }
    
    public function setParamRuleName($key, $ruleName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['rulename'] = $ruleName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleName($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['rulename'];
    }
    
    public function isParamRuleNameExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['rulename']);
    }
    
    
    
     public function getParamRule($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key];
    }
    
    public function setParamRuleConnectorSourceId($key, $connectorSouceId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['connector']['source'] = $connectorSouceId;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleConnectorSourceId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['connector']['source'];
    }
    
    public function setParamRuleConnectorCibleId($key, $connectorCibleId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['connector']['cible'] = $connectorCibleId;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleConnectorCibleId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['connector']['cible'];
    }
    
    
    public function getParamRuleLastKey()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['key'])?$myddlewareSession['param']['rule']['key'] : null;
    }
    
     public function setParamRuleLastKey($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        if($key == 0 || !isset($myddlewareSession['param']['rule']['key'][$key])){
           $myddlewareSession['param']['rule']['key'] = $key;
        }
        
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function setParamRuleLastId($key, $id)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['last_version_id'] = $id;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleLastId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['last_version_id'];
    }
        
    public function isParamRuleLastVersionIdExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['last_version_id']);
    }
    
    public function isParamRuleLastVersionIdEmpty($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return empty($myddlewareSession['param']['rule'][$key]['last_version_id']);
    }
    
    
    
    public function setParamRuleSourceSolution($key, $solutionName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['solution'] = $solutionName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceSolution($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['source']['solution']) ? $myddlewareSession['param']['rule'][$key]['source']['solution'] : null ;
    }
    
    public function getParamRuleSource($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['source'];
    }
    
    
    public function setParamRuleSourceConnector($key, $connectorName, $connectorValue)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source'][$connectorName] = $connectorValue;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceConnector($key, $connectorName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['source'][$connectorName];
    }
    
    
    public function setParamRuleCibleSolution($key, $solutionName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['cible']['solution'] = $solutionName;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleSolution($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['cible']['solution'];
    }
    
    public function setParamRuleCibleConnector($key, $connectorName, $connectorValue)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['cible'][$connectorName] = $connectorValue;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleConnector($key,$connectorName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['cible'][$connectorName];
    }
   
    
    public function getParamRuleCible($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['cible'];
    }

    public function setParamRuleReloadParams($key, $params)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['reload']['params'] = json_encode($params);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleReloadParams($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule'][$key]['reload']['params']);
    } 
    
    public function setParamRuleReloadFields($key, $fields)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['reload']['fields'] = json_encode($fields);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleReloadRelate($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule'][$key]['reload']['relate']);
    }
    
    public function setParamRuleReloadRelate($key, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['reload']['relate'] = json_encode($value);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleReloadFields($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule'][$key]['reload']['fields']);
    }

    public function setParamRuleReloadFilter($key, $filter)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['reload']['filter'] = json_encode($filter);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleReloadFilter($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule'][$key]['reload']['filter']);
    }        
    
    public function setParamRuleSourceModule($key, $moduleSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['module'] = $moduleSource;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceModule($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['source']['module'];
    }
    
    
    public function setParamRuleSourceDateReference($key, $dateReferenceSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['datereference'] = $dateReferenceSource;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceDateReference($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['source']['datereference'];
    }
    
    public function isParamRuleSourceDateReference($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['source']['datereference']);
    }
    
    
    public function setParamRuleSourceFields($key,$fieldsSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['fields'] = $fieldsSource;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceFields($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['source']['fields'];
    }
    
     public function setParamRuleSourceFieldsError($key,$fieldsSourceError)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['fields']['error'] = $fieldsSourceError;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceFieldsError($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['source']['fields']['error'];
    }
    
    public function isParamRuleSourceFieldsErrorExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['source']['fields']['error']);
    }
    
    public function isParamRuleSourceFieldsExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['source']['fields']);
    }
    
     public function isParamRuleTargetFieldsExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule'][$key]['target']['fields']);
    }
    

    public function setParamRuleCibleModule($key,$moduleTarget)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['cible']['module'] = $moduleTarget;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleModule($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['cible']['module'];
    } 

    public function setParamRuleCibleMode($key,$cibleMode)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['cible']['mode'] = $cibleMode;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleMode($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['cible']['mode'];
    } 
    
    public function setParamRuleTargetFields($key,$targetFields)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['target']['fields'] = $targetFields;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleFieldsByType($key,$type, $field = null)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $fields = $myddlewareSession['param']['rule'][$key][$type]['fields'];
        
        if($field != null){
            return $fields[$field];
        }else{
            return $fields;
        }
        
    } 
    
    
    public function getParamRuleTargetFields($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$key]['target']['fields'];
    } 
    
  
    public function setRuleId($key, $id)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['rule']['newid'][$key] = $id;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getRuleId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['rule']['newid'][$key];
    }
    
    public function removeRuleId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['rule']['newid'][$key] );
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function isRuleIdExist($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['rule']['newid'][$key]);
    }
    
    /**
     * If the rulename is less than X characters
     * @return boolean
     */
    public function isRuleNameLessThanXCharacters($key, $number)
    {
	if ($this->getParamRuleSourceSolution($key) !=null || strlen($this->getParamRuleName($key)) < $number || $this->getParamRuleNameValid($key) == false) {
            return false;
        }else{
            return true;
        }
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
        return (!empty($myddlewareSession['flux_filter']['where']) ? $myddlewareSession['flux_filter']['where'] : null);
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
        return $myddlewareSession['flux_filter']['c']['rule'];
    }
    
     
    public function setFluxFilterGlobalStatus($gblstatus)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['gblstatus'] = $gblstatus;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterGlobalStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['gblstatus'];
    }
    
    public function setFluxFilterStatus($status)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['status'] = $status;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['status'];
    }
	
	public function setFluxFilterType($type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['type'] = $type;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterType()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['type'];
    }
    
    
    
    public function setFluxFilterSourceId($sourceId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['source_id'] = $sourceId;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterTargetId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['target_id'];
    }
    
    public function setFluxFilterTargetId($targetId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['target_id'] = $targetId;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterSourceId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['source_id'];
    }
    
    public function setFluxFilterDateCreateStart($dateCreateStart)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_create_start'] = $dateCreateStart;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
	public function setFluxFilterSourceContent($sourceContent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['source_content'] = $sourceContent;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
	
    public function setFluxFilterTargetContent($targetContent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['target_content'] = $targetContent;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
	
    public function getFluxFilterDateCreateStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['date_create_start'];
    }
	
    public function getFluxFilterSourceContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['source_content'];
    }
    
    public function getFluxFilterTargetContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['target_content'];
    }
    
     public function setFluxFilterDateCreateEnd($dateCreateEnd)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_create_end'] = $dateCreateEnd;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterDateCreateEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['date_create_end'];
    }
    
    public function setFluxFilterDateModifStart($dateModifStart)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_modif_start'] = $dateModifStart;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterDateModifStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['date_modif_start'];
    }
    
    public function setFluxFilterDateModifEnd($dateModifEnd)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_modif_end'] = $dateModifEnd;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getFluxFilterDateModifEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['date_modif_end'];
    }
    
    public function removeFluxFilter()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    public function removeFluxFilterDateCreateStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_create_start']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    public function removeFluxFilterSourceContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['source_content']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    public function removeFluxFilterTargetContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['target_content']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeFluxFilterDateCreateEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_create_end']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    public function removeFluxFilterDateModifStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_modif_start']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeFluxFilterDateModifEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_modif_end']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
     public function removeFluxFilterRuleName()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['rule']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeFluxFilterStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['status']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeFluxFilterGblStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['gblstatus']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeFluxFilterTargetId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['target_id']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
	public function removeFluxFilterType()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['type']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
	
    public function removeFluxFilterSourceId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['source_id']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    
    

    public function isFluxFilterCSourceIdExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['source_id']);
    }
    
    public function isFluxFilterCTargetIdExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['target_id']);
    }
    
    public function isFluxFilterCWhereExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['where']);
    }
    
    
    public function isFluxFilterCGblStatusExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['gblstatus']);
    }
    
    public function isFluxFilterCStatusExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['status']);
    }
	
	public function isFluxFilterTypeExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['type']);
    }
    
    public function isFluxFilterCRuleExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['rule']);
    }
    
    public function isFluxFilterCExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']);
    }
    
    public function isFluxFilterCDateCreateStartExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['date_create_start']);
    }
    
    public function isFluxFilterCSourceContentExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['source_content']);
    }
    
    public function isFluxFilterCTargetContentExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['target_content']);
    }
    
    public function isFluxFilterCDateModifEndExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['date_modif_end']);
    }
    
     public function isFluxFilterCDateModifStartExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['date_modif_start']);
    }
    
    public function isFluxFilterCDateCreateEndExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']['date_create_end']);
    }
    
    public function isFluxFilterExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']);
    }
    
    ############# FLUX FILTER ###################
    
    
    ############# ERROR ###################
    
    public function setCreateRuleError($key, $message)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['error'][self::ERROR_CREATE_RULE_INDEX][$key] = $message;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getCreateRuleError($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['error'][self::ERROR_CREATE_RULE_INDEX][$key];
    }
    
    public function isErrorNotEmpty($key,$type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return !empty($myddlewareSession['error'][$type][$key]);
    }
    
     public function removeError($key,$type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['error'][$type][$key]);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    
    ############# ERROR ###################
    
    
    
    
    
    
}