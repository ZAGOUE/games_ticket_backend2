<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminStatsTest extends WebTestCase
{
    public function testAdminCanAccessOfferStats(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $password = 'Admin123!';
        $emailPrefix = uniqid();

        // Création d'un admin
        $adminEmail = $emailPrefix . '_admin@example.com';
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Stats',
            'email' => $adminEmail,
            'password' => $password
        ]));

        $admin = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        // Connexion de l'admin
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Créer une offre
        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], json_encode([
            'name' => 'Offre Stat',
            'description' => 'Offre pour stats',
            'price' => 20,
            'max_people' => 10
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Appel de l’API stats
        $client->request('GET', '/api/admin/stats/offers', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token,
        ]);
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertArrayHasKey('offer', $data[0]);
        $this->assertArrayHasKey('total', $data[0]);
    }
}
