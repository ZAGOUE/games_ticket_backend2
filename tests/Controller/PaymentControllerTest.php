<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $adminToken;
    private $userToken;
    private $offer;
    private $order;
    private $password = 'Password123!';
    private $emailPrefix;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->emailPrefix = uniqid();

        // Admin setup
        $adminEmail = $this->emailPrefix . '_admin@example.com';
        $this->client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Paiement',
            'email' => $adminEmail,
            'password' => $this->password
        ]));

        $admin = $this->em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $this->em->flush();

        $this->client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $this->password
        ]));
        $this->adminToken = json_decode($this->client->getResponse()->getContent(), true)['token'];

        // Créer une offre
        $this->client->request('POST', '/api/offers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->adminToken
        ], json_encode([
            'name' => 'Offre Paiement',
            'description' => 'Offre pour test paiement',
            'price' => 100,
            'max_people' => 50
        ]));
        $this->offer = $this->em->getRepository(\App\Entity\Offer::class)->findOneBy(['name' => 'Offre Paiement']);

        // Utilisateur setup
        $userEmail = $this->emailPrefix . '_user@example.com';
        $this->client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'User',
            'last_name' => 'Paiement',
            'email' => $userEmail,
            'password' => $this->password
        ]));

        $this->client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $userEmail,
            'password' => $this->password
        ]));
        $this->userToken = json_decode($this->client->getResponse()->getContent(), true)['token'];

        // Créer une commande
        $this->client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $this->userToken
        ], json_encode([
            'offer_id' => $this->offer->getId(),
            'quantity' => 1
        ]));

        $this->order = $this->em->getRepository(\App\Entity\TicketOrder::class)->findOneBy([
            'user' => $this->em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $userEmail]),
            'offer' => $this->offer
        ]);
    }

    public function testUserCanPayOrder(): void
    {
        $this->client->request('POST', '/api/orders/' . $this->order->getId() . '/pay', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->userToken
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Paiement effectué avec succès', $data['message']);
        $this->assertArrayHasKey('order_key', $data);
    }



public function testAnotherUserCannotPaySomeoneElsesOrder(): void
{
    // Création d’un autre utilisateur
    $email = $this->emailPrefix . '_unauthorized@example.com';
    $this->client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
        'first_name' => 'Bad',
        'last_name' => 'Actor',
        'email' => $email,
        'password' => $this->password
    ]));

    $this->client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
        'email' => $email,
        'password' => $this->password
    ]));
    $unauthorizedToken = json_decode($this->client->getResponse()->getContent(), true)['token'];

    // Tentative de paiement par un autre utilisateur
    $this->client->request('POST', '/api/orders/' . $this->order->getId() . '/pay', [], [], [
        'HTTP_Authorization' => 'Bearer ' . $unauthorizedToken
    ]);

    $this->assertResponseStatusCodeSame(404);
    $data = json_decode($this->client->getResponse()->getContent(), true);
    $this->assertEquals('Commande non trouvée', $data['error'] ?? '');
}
    public function testUserCannotPayWithoutToken(): void
    {
        $this->client->request('POST', '/api/orders/' . $this->order->getId() . '/pay');
        $this->assertResponseStatusCodeSame(401);
    }
    public function testUserCannotPayNonExistentOrder(): void
    {
        $this->client->request('POST', '/api/orders/999999/pay', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->userToken
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserCannotPayOthersOrder(): void
    {
        // Création d’un deuxième utilisateur
        $otherEmail = $this->emailPrefix . '_other@example.com';
        $this->client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Autre',
            'last_name' => 'Utilisateur',
            'email' => $otherEmail,
            'password' => $this->password
        ]));

        $this->client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $otherEmail,
            'password' => $this->password
        ]));
        $otherToken = json_decode($this->client->getResponse()->getContent(), true)['token'];

        // Tentative de paiement par un autre utilisateur
        $this->client->request('POST', '/api/orders/' . $this->order->getId() . '/pay', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $otherToken
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminCanViewAllPayments(): void
    {
        // Appel direct à /api/payments avec le token admin déjà configuré
        $this->client->request('GET', '/api/payments', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $this->adminToken
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }



}