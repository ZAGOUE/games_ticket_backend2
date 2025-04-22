<?php

// tests/EventListener/ExceptionListenerFunctionalTest.php
namespace App\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExceptionListenerFunctionalTest extends WebTestCase
{
    public function testNotFoundReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/une-route-inexistante');

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame('error', $data['status']);
        $this->assertArrayHasKey('message', $data);
        $this->assertIsString($data['code']);
        $this->assertEquals('ticket_not_found', $data['code']);

    }

}

