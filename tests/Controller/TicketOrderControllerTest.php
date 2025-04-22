<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketOrderControllerTest extends WebTestCase
{
    public function testUserCanCreateOrder(): void
    {
        $client = static::createClient();
        $password = 'Password123!';
        $userEmail = 'user' . uniqid() . '@example.com';
        $adminEmail = 'admin' . uniqid() . '@example.com';

        // Créer un admin et une offre
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password
        ]));
        $em = self::getContainer()->get('doctrine')->getManager();
        $admin = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $em->flush();

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $adminToken = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $adminToken
        ], json_encode([
            'name' => 'Billet pour test commande',
            'description' => 'Offre test',
            'price' => 50,
            'max_people' => 5
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Créer un utilisateur et se connecter
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User',
            'last_name' => 'Test',
            'email' => $userEmail,
            'password' => $password
        ]));
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $userEmail,
            'password' => $password
        ]));
        $userToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Récupérer l’offre
        $offer = $em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Billet pour test commande']);

        // Créer une commande
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $userToken
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 2
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    public function testUserCanListHisOrders(): void
    {
        $client = static::createClient();
        $password = 'Password123!';
        $userEmail = 'user_' . uniqid() . '@example.com';
        $adminEmail = 'admin_' . uniqid() . '@example.com';

        // Créer l'admin
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password
        ]));

        $em = self::getContainer()->get('doctrine')->getManager();
        $admin = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $em->flush();

        // Connexion de l'admin
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $adminToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Création d'une offre
        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $adminToken
        ], json_encode([
            'name' => 'Offre List Commande',
            'description' => 'Test listing',
            'price' => 60,
            'max_people' => 5
        ]));
        $offer = $em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre List Commande']);

        // Créer un utilisateur et se connecter
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User',
            'last_name' => 'Test',
            'email' => $userEmail,
            'password' => $password
        ]));
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $userEmail,
            'password' => $password
        ]));
        $userToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Création d'une commande
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $userToken
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 1
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Appel GET /api/orders
        $client->request('GET', '/api/orders', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $userToken
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function testUserCanGetSpecificOrder(): void
    {
        $client = static::createClient();
        $password = 'Password123!';
        $userEmail = 'user_' . uniqid() . '@example.com';
        $adminEmail = 'admin_' . uniqid() . '@example.com';

        // Créer l'admin
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password
        ]));

        $em = self::getContainer()->get('doctrine')->getManager();
        $admin = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $em->flush();

        // Connexion admin
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $adminToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Créer une offre
        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $adminToken
        ], json_encode([
            'name' => 'Offre unique',
            'description' => 'Spécifique',
            'price' => 70,
            'max_people' => 10
        ]));
        $offer = $em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre unique']);

        // Créer un utilisateur et se connecter
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User',
            'last_name' => 'Test',
            'email' => $userEmail,
            'password' => $password
        ]));

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $userEmail,
            'password' => $password
        ]));
        $userToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Créer une commande
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $userToken
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 1
        ]));
        $this->assertResponseStatusCodeSame(201);

        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $userEmail]);
        $order = $em->getRepository(\App\Entity\TicketOrder::class)->findOneBy(['user' => $user, 'offer' => $offer]);

        // Appel GET /api/orders/{id}
        $client->request('GET', '/api/orders/' . $order->getId(), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $userToken
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($order->getId(), $data['id']);

    }
    public function testUserCannotAccessOthersOrder(): void
    {
        $client = static::createClient();
        $password = 'Password123!';
        $adminEmail = 'admin_' . uniqid() . '@example.com';
        $user1Email = 'user1_' . uniqid() . '@example.com';
        $user2Email = 'user2_' . uniqid() . '@example.com';

        // Création de l'admin
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password
        ]));

        $em = self::getContainer()->get('doctrine')->getManager();
        $admin = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $em->flush();

        // Connexion admin
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $adminToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Création d'une offre
        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $adminToken
        ], json_encode([
            'name' => 'Offre privée',
            'description' => 'Access control',
            'price' => 100,
            'max_people' => 5
        ]));
        $offer = $em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre privée']);

        // Création de User1
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User1',
            'last_name' => 'Test',
            'email' => $user1Email,
            'password' => $password
        ]));
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user1Email,
            'password' => $password
        ]));
        $user1Token = json_decode($client->getResponse()->getContent(), true)['token'];

        // User1 crée une commande
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $user1Token
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 1
        ]));
        $this->assertResponseStatusCodeSame(201);
        $order = $em->getRepository(\App\Entity\TicketOrder::class)->findOneBy(['offer' => $offer]);

        // Création de User2
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User2',
            'last_name' => 'Test',
            'email' => $user2Email,
            'password' => $password
        ]));
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user2Email,
            'password' => $password
        ]));
        $user2Token = json_decode($client->getResponse()->getContent(), true)['token'];

        // User2 tente d'accéder à la commande de User1
        $client->request('GET', '/api/orders/' . $order->getId(), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $user2Token
        ]);

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Accès refusé', $data['error'] ?? '');

    }

    public function testGetNonExistentOrderReturns404(): void
    {
        $client = static::createClient();
        $password = 'Password123!';
        $email = 'user_' . uniqid() . '@example.com';

        // Créer un utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Ghost',
            'last_name' => 'User',
            'email' => $email,
            'password' => $password
        ]));

        // Se connecter
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Appel GET vers un ID inexistant
        $client->request('GET', '/api/orders/999999', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testListOrdersWithoutTokenReturns401(): void
    {
        $client = static::createClient();

        // Appel sans token
        $client->request('GET', '/api/orders');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUserCanDownloadHisTicket(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get('doctrine')->getManager();

        $email = 'ticket' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Création et connexion d’un utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Jean',
            'last_name' => 'Billet',
            'email' => $email,
            'password' => $password
        ]));

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Création d’une offre par un faux "admin"
        $offer = new \App\Entity\Offer();
        $offer->setName('Billet PDF');
        $offer->setDescription('PDF généré');
        $offer->setPrice(42);
        $offer->setMaxPeople(2);
        $em->persist($offer);
        $em->flush();

        // Création d’une commande
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 1
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Paiement de la commande
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        $order = $em->getRepository(\App\Entity\TicketOrder::class)->findOneBy(['user' => $user]);

        $client->request('POST', '/api/orders/' . $order->getId() . '/pay', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);
        $this->assertResponseIsSuccessful();

        // Télécharger le billet (PDF attendu)
        $client->request('GET', '/api/orders/' . $order->getId() . '/download', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('application/pdf', $client->getResponse()->headers->get('Content-Type'));
    }

    public function testAdminCanAccessAllOrders(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get('doctrine')->getManager();

        $email = 'admin_all_orders_' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Création d'un admin
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Global',
            'email' => $email,
            'password' => $password
        ]));

        $admin = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        $admin->setRoles(['ROLE_ADMIN']);
        $em->flush();

        // Connexion admin
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Appel GET /api/orders/all
        $client->request('GET', '/api/orders/all', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }


}
