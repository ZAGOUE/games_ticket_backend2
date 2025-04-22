<?php

// tests/OfferControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OfferControllerTest extends WebTestCase
{

    private $client;
    private $token;
    private $email;
    private $password;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $this->email = 'admin_' . uniqid() . '@example.com';
        $this->password = 'Password123!';

        // Création de l’admin
        $this->client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $this->email,
            'password' => $this->password
        ]));

        $em = self::getContainer()->get('doctrine')->getManager();
        $admin = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $this->email]);
        $admin->setRoles(['ROLE_ADMIN']);
        $em->flush();

        // Connexion
        $this->client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $this->email,
            'password' => $this->password
        ]));

        $this->token = json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    public function testAdminCanCreateOffer(): void
    {
        $this->client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ], json_encode([
            'name' => 'Billet Test',
            'description' => 'Test unitaire',
            'price' => 25,
            'max_people' => 5
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    public function testGetAllOffers(): void
    {
        $this->client->request('GET', '/api/offers', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testGetOfferById(): void
    {
        $this->client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->token,
        ], json_encode([
            'name' => 'Offre unique',
            'description' => 'Test get by id',
            'price' => 10.5,
            'max_people' => 50
        ]));

        $em = self::getContainer()->get('doctrine')->getManager();
        $offer = $em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre unique']);
        $this->assertNotNull($offer);

        $this->client->request('GET', '/api/offers/' . $offer->getId(), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Offre unique', $data['name']);
    }
    public function testUpdateOffer(): void
    {
        $this->client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ], json_encode([
            'name' => 'Offre à modifier',
            'description' => 'Avant modif',
            'price' => 40,
            'max_people' => 10
        ]));

        $em = self::getContainer()->get('doctrine')->getManager();
        $offer = $em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre à modifier']);
        $this->assertNotNull($offer);
        $id = $offer->getId();

        $this->client->request('PUT', '/api/offers/' . $id, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ], json_encode([
            'name' => 'Offre modifiée',
            'description' => 'Après modif',
            'price' => 99.99,
            'max_people' => 99
        ]));

        $this->assertResponseIsSuccessful();
        $em->clear();
        $updated = $em->getRepository(\App\Entity\Offer::class)->find($id);
        $this->assertEquals('Offre modifiée', $updated->getName());
        $this->assertEquals('Après modif', $updated->getDescription());
        $this->assertEquals(99.99, $updated->getPrice());
        $this->assertEquals(99, $updated->getMaxPeople());
    }

    public function testDeleteOffer(): void
    {
        $this->client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ], json_encode([
            'name' => 'Offre à supprimer',
            'description' => 'Suppression',
            'price' => 20,
            'max_people' => 20
        ]));

        $em = self::getContainer()->get('doctrine')->getManager();
        $offer = $em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre à supprimer']);
        $this->assertNotNull($offer);
        $id = $offer->getId();

        $this->client->request('DELETE', '/api/offers/' . $id, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->token
        ]);

        $this->assertResponseIsSuccessful();

        $deleted = $em->getRepository(\App\Entity\Offer::class)->find($id);
        $this->assertNull($deleted);
    }
}


