<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VerifyTicketTest extends WebTestCase
{
    public function testControllerCanVerifyPaidTicket(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // 1. Créer un ADMIN et une offre
        $adminEmail = 'admin' . uniqid() . '@example.com';
        $password = 'Admin123!';

        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password
        ]));
        $admin = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $adminToken = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $adminToken,
        ], json_encode([
            'name' => 'Offre JO Test',
            'description' => 'Épreuve Test',
            'price' => 100,
            'max_people' => 100
        ]));

        $offer = $entityManager->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre JO Test']);
        $this->assertNotNull($offer);

        // 2. Créer un USER et commander
        $userEmail = 'user' . uniqid() . '@example.com';

        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User',
            'last_name' => 'Testeur',
            'email' => $userEmail,
            'password' => $password
        ]));

        $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $userEmail]);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $userEmail,
            'password' => $password
        ]));
        $userToken = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $userToken,
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 1
        ]));
        $this->assertResponseStatusCodeSame(201);

        // 3. Récupérer la commande + la payer
        $order = $entityManager->getRepository(\App\Entity\TicketOrder::class)->findOneBy(['user' => $user, 'offer' => $offer]);
        $this->assertNotNull($order);

        $client->request('POST', '/api/orders/' . $order->getId() . '/pay', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $userToken,
        ]);
        $this->assertResponseIsSuccessful();

        $payResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('order_key', $payResponse);
        $orderKey = $payResponse['order_key'];


        // ️ 4. Créer un contrôleur
        $controllerEmail = 'controller' . uniqid() . '@example.com';

        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Contrôleur',
            'last_name' => 'Billet',
            'email' => $controllerEmail,
            'password' => $password
        ]));

        $controller = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $controllerEmail]);
        $controller->setRoles(['ROLE_CONTROLLER']);
        $entityManager->flush();

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $controllerEmail,
            'password' => $password
        ]));
        $controllerToken = json_decode($client->getResponse()->getContent(), true)['token'];

        //  5. Vérifier le billet via /verify-ticket/{order_key}
        $client->request('GET', '/api/orders/verify-ticket/' . $orderKey, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $controllerToken,
        ]);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('success', $responseData['status']);

    }

    public function testCannotVerifyAlreadyUsedTicket(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        //  Reprend le même scénario : création admin + offre
        $emailPrefix = uniqid(); // pour tout isoler proprement
        $password = 'Test123!';

        // Création ADMIN
        $adminEmail = $emailPrefix . '_admin@example.com';
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password
        ]));
        $admin = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password
        ]));
        $adminToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Créer l'offre
        $client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $adminToken,
        ], json_encode([
            'name' => 'Offre Double Scan',
            'description' => 'Test réutilisation billet',
            'price' => 80,
            'max_people' => 50
        ]));
        $offer = $entityManager->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre Double Scan']);
        $this->assertNotNull($offer);

        // Création USER + commande + paiement
        $userEmail = $emailPrefix . '_user@example.com';
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User',
            'last_name' => 'Testeur',
            'email' => $userEmail,
            'password' => $password
        ]));
        $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $userEmail]);

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $userEmail,
            'password' => $password
        ]));
        $userToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // Commande
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $userToken,
        ], json_encode([
            'offer_id' => $offer->getId(),
            'quantity' => 1
        ]));
        $this->assertResponseStatusCodeSame(201);

        $order = $entityManager->getRepository(\App\Entity\TicketOrder::class)->findOneBy(['user' => $user, 'offer' => $offer]);
        $this->assertNotNull($order);

        // Paiement
        $client->request('POST', '/api/orders/' . $order->getId() . '/pay', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $userToken,
        ]);
        $this->assertResponseIsSuccessful();
        $orderKey = json_decode($client->getResponse()->getContent(), true)['order_key'];

        // Création contrôleur
        $controllerEmail = $emailPrefix . '_controller@example.com';
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Contrôleur',
            'last_name' => 'Scan',
            'email' => $controllerEmail,
            'password' => $password
        ]));
        $controller = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $controllerEmail]);
        $controller->setRoles(['ROLE_CONTROLLER']);
        $entityManager->flush();

        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $controllerEmail,
            'password' => $password
        ]));
        $controllerToken = json_decode($client->getResponse()->getContent(), true)['token'];

        // 1ère vérification : success
        $client->request('GET', '/api/orders/verify-ticket/' . $orderKey, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $controllerToken,
        ]);
        $this->assertResponseIsSuccessful();

        //  2e vérification : échec attendu
        $client->request('GET', '/api/orders/verify-ticket/' . $orderKey, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $controllerToken,
        ]);
        $this->assertResponseStatusCodeSame(400); // ou 403 selon ton choix
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('ticket_already_used', $data['code']);
        $this->assertStringContainsString('déjà été utilisé', $data['message']);
    }

}
