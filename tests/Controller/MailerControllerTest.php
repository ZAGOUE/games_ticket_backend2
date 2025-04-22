<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MailerControllerTest extends WebTestCase
{
    public function testSendEmailApi(): void
    {
        $client = static::createClient();

        // Création utilisateur + login
        $email = 'mailtest' . uniqid() . '@example.com';
        $password = 'Password123!';
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Jean',
            'last_name' => 'Email',
            'email' => $email,
            'password' => $password
        ]));
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Appel protégé avec token
        $client->request('POST', '/api/send-email', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Email envoyé avec succès !', $data['message']);
    }

}
