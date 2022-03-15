<?php

namespace App\Tests\Controller;

// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
// use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

// class MailControllerTest extends WebTestCase
// {
//     use MailerAssertionsTrait;
    
//     // protected function setUp(): void
//     // {
//     //     $this->client = static::createClient();
//     // }

//     public function testMailIsSentAndContentIsOk()
//     {
//         $client = $this->createClient();
//         $client->request('GET', '/email');
//         $this->assertResponseIsSuccessful();

//         $this->assertEmailCount(1);

//         $email = $this->getMailerMessage();

//         $this->assertEmailHtmlBodyContains($email, 'Welcome');
//         $this->assertEmailTextBodyContains($email, 'Welcome');
//     }

//     // public function testMailIsSentAndContentIsOk()
//     // {
//     //     // enables the profiler for the next request (it does nothing if the profiler is not available)
//     //     $this->client->enableProfiler();

//     //     $crawler = $this->client->request('POST', '/managementsmtp/readConfig');

//     //     $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

//     //     // checks that an email was sent
//     //     $this->assertSame(1, $mailCollector->getMessageCount());

//     //     $collectedMessages = $mailCollector->getMessages();
//     //     $message = $collectedMessages[0];

//     //     // Asserting email data
//     //     $this->assertInstanceOf('Swift_Message', $message);
//     //     $this->assertSame('Hello Email', $message->getSubject());
//     //     $this->assertSame('send@example.com', key($message->getFrom()));
//     //     $this->assertSame('recipient@example.com', key($message->getTo()));
//     //     $this->assertSame(
//     //         'You should see me from the profiler!',
//     //         $message->getBody()
//     //     );
//     // }
// }
