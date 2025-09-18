<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages Myddleware Session ( Refactoring since the Controllers ).
 *
 * @author Dolyveen Renault <drenault@karudev-informatique.fr>
 */
class SessionService
{
    const MYDDLEWARE_SESSION_INDEX = 'myddlewareSession';
    private $requestStack;

    const ERROR_CREATE_RULE_INDEX = 'create_rule';

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function getMyddlewareSession()
    {
        $session = $this->getSession();
        if (!$session->has(self::MYDDLEWARE_SESSION_INDEX)) {
            $session->set(self::MYDDLEWARE_SESSION_INDEX, []);
        }

        return $session->get(self::MYDDLEWARE_SESSION_INDEX);
    }

    //############ SOLUTION ###################

    public function setSolutionName($solutionName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['solution']['callback'] = $solutionName;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getSolutionName()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['myddleware']['connector']['solution']['callback'];
    }

    public function isSolutionNameExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['myddleware']['connector']['solution']['callback']);
    }

    public function setSolutionType()
    {
    }

    public function getSolutionType($type)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['myddleware']['connector'][0]['solution'][$type];
    }

    //############ SOLUTION ###################

    //############ UPLOAD ###################

    public function getUploadName()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['myddleware']['upload']['name'];
    }

    public function setUploadName($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['upload']['name'] = $value;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeUpload()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['upload']);

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function isUploadNameExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['myddleware']['upload']['name']);
    }

    public function isUploadErrorExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['myddleware']['upload']['error']);
    }

    //############ UPLOAD ###################

    //############ CONNECTOR ###################

    public function isConnectorExist(): bool
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setParamConnectorAddType($value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['add']['type'] = $value;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getConnectorAddMessage()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['myddleware']['connector']['add']['message'];
    }

    public function setConnectorValues()
    {
    }

    public function getConnectorValues()
    {
    }

    public function setParamConnectorSource($source)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['connector']['source'] = $source;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

    public function isParamConnectorSourceExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['connector']['source']);
    }

    public function isParamRuleSourceModuleExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['source']['module']);
    }

    public function isParamRuleCibleModuleExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['cible']['module']);
    }

    public function isParamRuleCibleModeExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['cible']['mode']);
    }

    public function setParamConnectorSourceToken($token)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['connector']['source']['token'] = $token;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamConnectorSourceToken()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return json_decode($myddlewareSession['param']['connector']['source']['token']);
    }

    public function isParamConnectorSourceTokenExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['connector']['source']['token']);
    }

    public function setParamConnectorSourceRefreshToken($refreshToken)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['connector']['source']['refreshToken'] = $refreshToken;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamConnectorSourceRefreshToken()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return json_decode($myddlewareSession['param']['connector']['source']['refreshToken']);
    }

    public function isParamConnectorSourceRefreshTokenExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['connector']['source']['refreshToken']);
    }

    public function setParamConnectorSolutionSource($key, $source)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector'][$key]['solution']['source'] = json_encode($source);

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamConnectorParentType($parent, $type)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['connector'][$parent][$type];
    }

    public function isParamConnectorExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['connector']);
    }

    public function removeConnector()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['connector']); //L391 in ConnectorController

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeMyddlewareConnector()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['connector']);

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeConnectorAdd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['connector']['add']);

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeConnectorValues()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['myddleware']['connector']['values']);

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function isConnectorValuesExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['myddleware']['connector']['values']);
    }

    //############ CONNECTOR ###################

    //############ MAILCHIMP ###################

    public function setMailchimpParamConnexion($redirectUri, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['myddleware']['connector']['mailchimp'][$redirectUri]['paramConnexion'] = $value;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getMailchimpParamConnexion($redirectUri)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['myddleware']['connector']['mailchimp'][$redirectUri]['paramConnexion'];
    }

    //############ MAILCHIMP ###################

    //############ RULE ###################

    public function removeParamRule($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['rule'][$key]);

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeParamParentRule($key, $parent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['param']['rule'][$key][$parent]);

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setParamParentRule($key, $parent, $value)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key][$parent] = $value;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleParentName($key, $parent, $name)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key][$parent][$name];
    }

    public function isParamRuleExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]);
    }

    public function setParamRuleNameValid($key, $isValid)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['rulename_valide'] = $isValid;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleNameValid($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return (bool) $myddlewareSession['param']['rule'][$key]['rulename_valide'];
    }

    public function setParamRuleName($key, $ruleName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['rulename'] = $ruleName;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleName($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['rulename'];
    }

    public function isParamRuleNameExist($key): bool
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleConnectorCibleId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['connector']['cible'];
    }

    public function getParamRuleLastKey()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule']['key']) ? $myddlewareSession['param']['rule']['key'] : null;
    }

    public function setParamRuleLastKey($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        if (0 == $key || !isset($myddlewareSession['param']['rule']['key'][$key])) {
            $myddlewareSession['param']['rule']['key'] = $key;
        }

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setParamRuleLastId($key, $id)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['last_version_id'] = $id;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleLastId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['last_version_id'];
    }

    public function isParamRuleLastVersionIdExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['last_version_id']);
    }

    public function isParamRuleLastVersionIdEmpty($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return empty($myddlewareSession['param']['rule'][$key]['last_version_id']);
    }

    public function setParamRuleSourceSolution($key, $solutionName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['solution'] = $solutionName;

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleSourceSolution($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['source']['solution']) ? $myddlewareSession['param']['rule'][$key]['source']['solution'] : null;
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleCibleConnector($key, $connectorName)
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
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

        $session = $this->getSession();
        $session->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleSourceDateReference($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['source']['datereference'];
    }

    public function isParamRuleSourceDateReference($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['source']['datereference']);
    }

    public function setParamRuleSourceFields($key, $fieldsSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['fields'] = $fieldsSource;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleSourceFields($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['source']['fields'];
    }

    public function setParamRuleSourceFieldsError($key, $fieldsSourceError)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['source']['fields']['error'] = $fieldsSourceError;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleSourceFieldsError($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['source']['fields']['error'];
    }

    public function isParamRuleSourceFieldsErrorExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['source']['fields']['error']);
    }

    public function isParamRuleSourceFieldsExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['source']['fields']);
    }

    public function isParamRuleTargetFieldsExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['param']['rule'][$key]['target']['fields']);
    }

    public function setParamRuleCibleModule($key, $moduleTarget)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['cible']['module'] = $moduleTarget;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleCibleModule($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['cible']['module'];
    }

    public function setParamRuleCibleMode($key, $cibleMode)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['cible']['mode'] = $cibleMode;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleCibleMode($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['param']['rule'][$key]['cible']['mode'];
    }

    public function setParamRuleTargetFields($key, $targetFields)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['param']['rule'][$key]['target']['fields'] = $targetFields;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getParamRuleFieldsByType($key, $type, $field = null)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $fields = $myddlewareSession['param']['rule'][$key][$type]['fields'];

        if (null != $field) {
            return $fields[$field];
        }

        return $fields;
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

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getRuleId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['rule']['newid'][$key];
    }

    public function removeRuleId($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['rule']['newid'][$key]);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function isRuleIdExist($key): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['rule']['newid'][$key]);
    }

    public function isRuleNameLessThanXCharacters($key, $number): bool
    {
        if (null != $this->getParamRuleSourceSolution($key) || strlen($this->getParamRuleName($key)) < $number || !$this->getParamRuleNameValid($key)) {
            return false;
        }

        return true;
    }

    //############ RULE ###################

    //############ FLUX FILTER ###################

    public function setFluxFilterWhere($where)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        if (!empty($where)) {
            $myddlewareSession['flux_filter']['customWhere'] = $where;
        }
        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterWhere()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $customWhere = null;
        if (!empty($myddlewareSession['flux_filter']['customWhere'])) {
            $customWhere = $myddlewareSession['flux_filter'];
        }

        return $customWhere;
    }

    public function setFluxFilterRuleName($ruleName)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['rule'] = $ruleName;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterRuleName()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['rule'] ?? null;
    }

    public function setFluxFilterGlobalStatus($gblstatus)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['gblstatus'] = $gblstatus;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterGlobalStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['gblstatus'] ?? null;
    }

    public function setFluxFilterStatus($status)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['status'] = $status;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['status'] ?? null;
    }

    public function setFluxFilterType($type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['type'] = $type;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterType()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['type'] ?? null;
    }

    public function getFluxFilterReference()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['reference'] ?? null;
    }

    public function setFluxFilterReference($reference)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['reference'] = $reference;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterReference()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['reference']);
        
        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setFluxFilterSourceId($sourceId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['source_id'] = $sourceId;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setFluxFilterModuleSource($moduleSource)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['module_source'] = $moduleSource;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterTargetId()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['target_id'] ?? null;
    }

    public function setFluxFilterTargetId($targetId)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['target_id'] = $targetId;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterSourceId()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['source_id'] ?? null;
    }

    public function getFluxFilterModuleSource()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['module_source'] ?? null;
    }

    public function setFluxFilterModuleTarget($moduleTarget)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['module_target'] = $moduleTarget;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterModuleTarget()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['module_target'] ?? null;
    }

    public function setFluxFilterDateCreateStart($dateCreateStart)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_create_start'] = $dateCreateStart;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setFluxFilterSourceContent($sourceContent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['source_content'] = $sourceContent;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setFluxFilterTargetContent($targetContent)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['target_content'] = $targetContent;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterDateCreateStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['date_create_start'] ?? null;
    }

    public function getFluxFilterOperators()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['operators'] ?? null;
    }


    public function getFluxFilterSourceContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['source_content'] ?? null;
    }

    public function getFluxFilterTargetContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['target_content'] ?? null;
    }


    public function setFluxFilterOperators($operators)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['operators'] = $operators;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterOperators()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['operators']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function setFluxFilterDateCreateEnd($dateCreateEnd)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_create_end'] = $dateCreateEnd;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterDateCreateEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['date_create_end'] ?? null;
    }

    public function setFluxFilterDateModifStart($dateModifStart)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_modif_start'] = $dateModifStart;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterDateModifStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['date_modif_start'] ?? null;
    }

    public function setFluxFilterDateModifEnd($dateModifEnd)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['date_modif_end'] = $dateModifEnd;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getFluxFilterDateModifEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['date_modif_end'] ?? null;
    }

    // public function removeFluxFilter()
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();
    //     unset($myddlewareSession['flux_filter']);

    //     $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    // }

    public function removeFluxFilterDateCreateStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_create_start']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterSourceContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['source_content']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterTargetContent()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['target_content']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterDateCreateEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_create_end']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterDateModifStart()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_modif_start']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterDateModifEnd()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['date_modif_end']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterRuleName()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['rule']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    // Set sort order
    public function setFluxFilterSortOrder($sortOrder)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['sort_order'] = $sortOrder;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    // Get sort order
    public function getFluxFilterSortOrder()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['sort_order'] ?? null;
    }

    // Remove sort order
    public function removeFluxFilterSortOrder()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['sort_order']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    // Set sort field
    public function setFluxFilterSortField($sortField)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['flux_filter']['c']['sort_field'] = $sortField;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    // Get sort field
    public function getFluxFilterSortField()
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['flux_filter']['c']['sort_field'] ?? null;
    }

    // Remove sort field
    public function removeFluxFilterSortField()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['sort_field']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['status']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterGlobalStatus()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['gblstatus']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterTargetId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['target_id']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterModuleSource()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['module_source']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterModuleTarget()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['module_target']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterType()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['type']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function removeFluxFilterSourceId()
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['flux_filter']['c']['source_id']);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    // public function isFluxFilterCSourceIdExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['source_id']);
    // }

    // public function isFluxFilterCTargetIdExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['target_id']);
    // }

    public function isFluxFilterCWhereExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['flux_filter']['c']['where']);
    }

    // public function isFluxFilterCGblStatusExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['gblstatus']);
    // }

    // public function isFluxFilterCStatusExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['status']);
    // }

    // public function isFluxFilterTypeExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['type']);
    // }

    // public function isFluxFilterCRuleExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['rule']);
    // }

    public function isFluxFilterCExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['flux_filter']['c']);
    }

    public function isFluxFilterCDateCreateStartExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['flux_filter']['c']['date_create_start']);
    }

    // public function isFluxFilterCSourceContentExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['source_content']);
    // }

    // public function isFluxFilterCTargetContentExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['target_content']);
    // }

    // public function isFluxFilterCDateModifEndExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['date_modif_end']);
    // }

    // public function isFluxFilterCDateModifStartExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']['c']['date_modif_start']);
    // }

    public function isFluxFilterCDateCreateEndExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['flux_filter']['c']['date_create_end']);
    }

    
    public function isFluxFilterCModuleSourceExist(): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return isset($myddlewareSession['flux_filter']['c']['module_source']);
    }

    
    // public function isFluxFilterExist(): bool
    // {
    //     $myddlewareSession = $this->getMyddlewareSession();

    //     return isset($myddlewareSession['flux_filter']);
    // }

    //############ FLUX FILTER ###################

    //############ ERROR ###################

    public function setCreateRuleError($key, $message)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        $myddlewareSession['error'][self::ERROR_CREATE_RULE_INDEX][$key] = $message;

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    public function getCreateRuleError($key)
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return $myddlewareSession['error'][self::ERROR_CREATE_RULE_INDEX][$key];
    }

    public function isErrorNotEmpty($key, $type): bool
    {
        $myddlewareSession = $this->getMyddlewareSession();

        return !empty($myddlewareSession['error'][$type][$key]);
    }

    public function removeError($key, $type)
    {
        $myddlewareSession = $this->getMyddlewareSession();
        unset($myddlewareSession['error'][$type][$key]);

        $this->getSession()->set(self::MYDDLEWARE_SESSION_INDEX, $myddlewareSession);
    }

    //############ ERROR ###################
}
