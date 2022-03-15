<?php

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome to EasyAdmin 3');
    }

    public function testPageIsAccessibleToAuthenticatedUser(): void
    {

    }

    public function testPageIsAccessibleToAdminUser(): void
    {
        
    }

    public function testPageIsNotAccessibleToAnonymousdUser(): void
    {
        
    }

    public function testClickOnLogoRedirectsToDashboard(): void
    {

    }    
}
