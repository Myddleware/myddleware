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
    
    public function isParamRuleSourceModuleExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['source']['module']);
    }
    
    public function isParamRuleCibleModuleExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['cible']['module']);
    }
    
    public function isParamRuleCibleModeExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['cible']['mode']);
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
    
      
    public function setParamConnectorSolutionSource($source)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['solution']['source'] = json_encode($source);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamConnectorSolutionSource()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['myddleware']['connector']['solution']['source']);
    }
    
    
     public function setParamConnectorSolutionTarget($target)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['solution']['target'] = json_encode($target);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamConnectorSolutionTarget()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['myddleware']['connector']['solution']['target']);
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
    
    public function removeParamRule()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['rule']);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function removeParamParentRule($parent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['rule'][$parent]);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function setParamParentRule($parent, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$parent] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamParentRule($parent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$parent];
    }
    
     
    public function setParamRuleConnectorParent($parent, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['connector'][$parent] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleConnectorParent($parent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['connector'][$parent];
    }
    
    public function setParamRuleParentName($parent, $name, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$parent][$name] = $value;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleParentName($parent, $name)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'][$parent][$name];
    }
    
    public function isParamRuleExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']); 
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
        return (bool)$myddlewareSession['param']['rule']['rulename_valide'];
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
    
    public function isParamRuleNameExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['rulename']);
    }
    
    
    
     public function getParamRule()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule'];
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
    
    public function isParamRuleLastVersionIdExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['last_version_id']);
    }
    
    public function isParamRuleLastVersionIdEmpty()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return empty($myddlewareSession['param']['rule']['last_version_id']);
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
        return isset($myddlewareSession['param']['rule']['source']['solution']) ? $myddlewareSession['param']['rule']['source']['solution'] : null ;
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
   
    
    public function getParamRuleCible()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['cible'];
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
    
    public function getParamRuleReloadRelate()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return json_decode($myddlewareSession['param']['rule']['reload']['relate']);
    }
    
    public function setParamRuleReloadRelate($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['reload']['relate'] = json_encode($value);
        
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
    
    
    public function setParamRuleSourceDateReference($dateReferenceSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['source']['datereference'] = $dateReferenceSource;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceDateReference()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['source']['datereference'];
    }
    
    public function isParamRuleSourceDateReference()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['source']['datereference']);
    }
    
    
    public function setParamRuleSourceFields($fieldsSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['source']['fields'] = $fieldsSource;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceFields()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['source']['fields'];
    }
    
     public function setParamRuleSourceFieldsError($fieldsSourceError)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['source']['fields']['error'] = $fieldsSourceError;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleSourceFieldsError()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['source']['fields']['error'];
    }
    
    public function isParamRuleSourceFieldsErrorExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['source']['fields']['error']);
    }
    
    public function isParamRuleSourceFieldsExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['source']['fields']);
    }
    
     public function isParamRuleTargetFieldsExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['param']['rule']['target']['fields']);
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

    public function setParamRuleCibleMode($cibleMode)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['cible']['mode'] = $cibleMode;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleCibleMode()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['cible']['mode'];
    } 
    
    public function setParamRuleTargetFields($targetFields)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule']['target']['fields'] = $targetFields;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getParamRuleFieldsByType($type, $field = null)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $fields = $myddlewareSession['param']['rule'][$type]['fields'];
        
        if($field != null){
            return $fields[$field];
        }else{
            return $fields;
        }
        
    } 
    
    
    public function getParamRuleTargetFields()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['param']['rule']['target']['fields'];
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
    
    public function isRuleIdExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['rule']['newid']);
    }
    
    /**
     * If the rulename is less than X characters
     * @return boolean
     */
    public function isRuleNameLessThanXCharacters($number)
    {
	if ($this->getParamRuleSourceSolution() !=null || strlen($this->getParamRuleName()) < $number || $this->getParamRuleNameValid() == false) {
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
    
    public function getFluxFilterDateCreateStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['flux_filter']['c']['date_create_start'];
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
    
    public function isFluxFilterCExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']['c']);
    }
    
    public function isFluxFilterExist()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return isset($myddlewareSession['flux_filter']);
    }
    
    ############# FLUX FILTER ###################
    
    
    ############# ERROR ###################
    
    public function setCreateRuleError($message)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['error'][self::ERROR_CREATE_RULE_INDEX] = $message;
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    public function getCreateRuleError()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return $myddlewareSession['error'][self::ERROR_CREATE_RULE_INDEX];
    }
    
    public function isErrorNotEmpty($type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        return !empty($myddlewareSession['error']['create_rule']);
    }
    
     public function removeError($type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['error'][$type]);
        
        $this->_session->set(self::MYDDLEWARE_SESSION_INDEX,$myddlewareSession);
    }
    
    
    ############# ERROR ###################
    
    
    
    
    
    
}