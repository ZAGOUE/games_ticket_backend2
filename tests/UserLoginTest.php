<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserLoginTest extends WebTestCase
{
    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();

        $email = 'claude' . uniqid() . '@example.com';

        // Création utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Login',
            'last_name' => 'Test',
            'email' => $email,
            'password' => 'Password123!'
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Authentification JWT
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => 'Password123!'
        ]));

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);
    }

}
