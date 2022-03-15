<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Rule;
use App\Entity\Document;
use App\Entity\Connector;
use App\Tests\DatabaseDependantTestCase;

class DashboardMenuItemsTest extends DatabaseDependantTestCase {

    
    public function testMenuItemsLinkToRespectiveCruds(): void
    {

        $this->testConnectorMenuLinksToConnectorCrud();
        $this->testRuleMenuLinksToRuleCrud();
        $this->testDocumentsMenuLinksToDocumentCrud();
    }
    
    public function testConnectorMenuLinksToConnectorCrud(): void
    {
        $repo = $this->entityManager->getRepository(Connector::class);

    } 
    
    public function testRuleMenuLinksToRuleCrud(): void
    {
        $repo = $this->entityManager->getRepository(Rule::class);
    }
    
    public function testDocumentsMenuLinksToDocumentCrud(): void
    {
        $repo = $this->entityManager->getRepository(Document::class);
    }
    
}