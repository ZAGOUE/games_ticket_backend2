<?php


namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminLogControllerTest extends WebTestCase
{
    public function testLogsEndpointReturnsJson(): void
    {
        $client = static::createClient();
        $email = 'logtest' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Création d'un utilisateur (simple)
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Logger',
            'last_name' => 'Test',
            'email' => $email,
            'password' => $password
        ]));

        // Connexion et récupération du token
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Appel de /api/admin_logs avec le token
        $client->request('GET', '/api/admin_logs', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

}
