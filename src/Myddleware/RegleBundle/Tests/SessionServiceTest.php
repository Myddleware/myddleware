<?php
namespace Myddleware\RegleBundle\Tests;

use PHPUnit\Framework\TestCase;
use Myddleware\RegleBundle\Service\SessionService;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\VarDumper\VarDumper;

class SessionServiceTest extends TestCase{
    
    private $sessionService;
    
    public function setUp() 
    {  
       $session = new Session(new MockArraySessionStorage());
       $sessionService = new SessionService($session);
       $this->sessionService = $sessionService;
    }
    
    
    public function solutionProvider()
    {
        return [
                ['mysql','prestashop','saleforce','suitecrm']
            ];
    }
            
            
    /**
     * @dataProvider solutionProvider
     */       
    public function testSetSolutionName($solutionName)
    {
        $this->sessionService->setSolutionName($solutionName);
        $this->assertEquals($solutionName, $this->sessionService->getSolutionName());
        
    }
    
    
    /*public function testGetMyddlewareSession()
    {
        //VarDumper::dump($this->sessionService->getMyddlewareSession());
    }*/
    
}