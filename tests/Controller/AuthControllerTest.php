<?php

// tests/Controller/AuthControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testLoginReturnsUserData(): void
    {
        $client = static::createClient();
        $email = 'auth' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Créer un utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => $password
        ]));

        // Login via /login_check
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));

        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Appel /api/login avec le token
        $client->request('POST', '/api/login', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($email, $data['email']);
        $this->assertContains('ROLE_USER', $data['roles']);
    }

}
