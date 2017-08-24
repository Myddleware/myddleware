<?php
namespace Myddleware\RegleBundle\Service;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Manage the session of myddleware
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
    
    
    public function getConnectorAnimation()
    {
        return null;
    }
    
    public function setConnectorAnimation()
    {
        return null;
    }
      
    public function setConnectorMessage()
    {
        return null;
    }
    
    public function getConnectorMessage()
    {
        return null;
    }
    
    
}