<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserRegistrationTest extends WebTestCase
{
    public function testUserRegistrationSuccess(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'paul' . uniqid() . '@example.com',

            'password' => 'StrongPassword123!'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testRegistrationFailsIfPasswordIsWeak(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Weak',
            'last_name' => 'Pass',
            'email' => 'weakpass@example.com',
            'password' => '123' // Trop faible
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('Le mot de passe doit contenir', $client->getResponse()->getContent());
    }
}
