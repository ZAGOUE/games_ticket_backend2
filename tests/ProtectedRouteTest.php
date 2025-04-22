<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProtectedRouteTest extends WebTestCase
{
    public function testAccessGrantedWithToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // ----------- ADMIN crée une offre -----------
        $adminEmail = 'admin' . uniqid() . '@example.com';
        $password = 'Admin123!';

        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Attribuer ROLE_ADMIN
        $userRepo = $entityManager->getRepository(\App\Entity\User::class);
        $admin = $userRepo->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        // Authentifier l'admin
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $adminToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Créer une offre
        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $adminToken,
        ], json_encode([
            'name' => 'Billet Test',
            'description' => 'Pour test PHPUnit',
            'price' => 100,
            'max_people' => 50
        ]));
        $this->assertResponseStatusCodeSame(201);

        $offerRepo = $entityManager->getRepository(\App\Entity\Offer::class);
        $offer = $offerRepo->findOneBy(['name' => 'Billet Test']);
        $this->assertNotNull($offer);

        // ----------- Utilisateur crée une commande -----------
        $userEmail = 'user' . uniqid() . '@example.com';

        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User',
            'last_name' => 'Test',
            'email' => $userEmail,
            'password' => $password
        ]));
        $this->assertResponseStatusCodeSame(201);

        $user = $userRepo->findOneBy(['email' => $userEmail]);

        // Authentifier l’utilisateur
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $userEmail,
            'password' => $password
        ]));
        $userToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Créer une commande
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $userToken,
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 1
        ]));
        $this->assertResponseStatusCodeSame(201);

        // ----------- Vérifier la commande créée -----------
        $orderRepo = $entityManager->getRepository(\App\Entity\TicketOrder::class);
        $order = $orderRepo->findOneBy(['user' => $user, 'offer' => $offer]);
        $this->assertNotNull($order);

        // Accéder à la commande via GET /api/orders/{id}
        $client->request('GET', '/api/orders/' . $order->getId(), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $userToken,
        ]);
        $this->assertResponseIsSuccessful();
    }


}
