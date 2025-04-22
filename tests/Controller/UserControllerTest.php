<?php


namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testAuthenticatedUserCanViewProfile(): void
    {
        $client = static::createClient();
        $email = 'profile' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Inscription
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $email,
            'password' => $password
        ]));

        // Récupérer l'utilisateur créé en base
        $em = self::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        $id = $user->getId();

        // Login
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Appel de /api/users/{id}
        $client->request('GET', '/api/users/' . $id, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame($email, $data['email']);
        $this->assertSame($id, $data['id']);
    }

    public function testUserCanBeUpdated(): void
    {
        $client = static::createClient();
        $email = 'update' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Création de l'utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Jean',
            'last_name' => 'Original',
            'email' => $email,
            'password' => $password
        ]));

        // Récupération utilisateur
        $em = self::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        $id = $user->getId();

        // Connexion
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Modification du prénom et du nom
        $client->request('PUT', '/api/users/' . $id, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], json_encode([
            'first_name' => 'JeanModif',
            'last_name' => 'MisAJour'
        ]));

        $this->assertResponseIsSuccessful();

        // Vérification de la mise à jour
        $client->request('GET', '/api/users/' . $id, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);
        $data = json_decode($client->getResponse()->getContent(), true);

        $user = $em->getRepository(\App\Entity\User::class)->find($id);


        $this->assertSame('JeanModif', $user->getFirstName());
        $this->assertSame('MisAJour', $user->getLastName());
    }

    public function testUserCanBeDeleted(): void
    {
        $client = static::createClient();
        $email = 'delete' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Création de l'utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Alice',
            'last_name' => 'ToDelete',
            'email' => $email,
            'password' => $password
        ]));

        // Récupération de l'utilisateur
        $em = self::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($user);
        $id = $user->getId();

        // Connexion
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Suppression de l'utilisateur
        $client->request('DELETE', '/api/users/' . $id, [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);
        $this->assertResponseIsSuccessful();

        // Vérifie que l'utilisateur n'existe plus en base
        $deletedUser = $em->getRepository(\App\Entity\User::class)->find($id);
        $this->assertNull($deletedUser);
    }

    public function testGetUserById(): void
    {
        $client = static::createClient();
        $email = 'view' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Création d'un utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Viewer',
            'last_name' => 'Test',
            'email' => $email,
            'password' => $password
        ]));

        // Connexion
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // Récupérer l'utilisateur en base
        $em = self::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);

        // Appel GET /api/users/{id}
        $client->request('GET', '/api/users/' . $user->getId(), [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($user->getId(), $data['id']);
        $this->assertSame($email, $data['email']);
    }

    public function testGetUserByIdReturns404IfUserNotFound(): void
    {
        $client = static::createClient();
        $email = 'user404_' . uniqid() . '@example.com';
        $password = 'Password123!';

        // Créer un utilisateur
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => $password
        ]));

        // Connexion
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $password
        ]));
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData); // ✅ Sécurité

        $token = $responseData['token'];

        // Appel avec un ID inexistant
        $client->request('GET', '/api/users/999999', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token
        ]);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Utilisateur non trouvé', $data['error']);
    }

    public function testAdminCanCreateUser(): void
    {
        $client = static::createClient();
        $password = 'Admin123!';
        $adminEmail = 'admin_' . uniqid() . '@example.com';

        // 1. Créer un admin
        $client->request('POST', '/api/users/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => $adminEmail,
            'password' => $password,
        ]));

        // Promouvoir l'utilisateur en admin
        $em = self::getContainer()->get('doctrine')->getManager();
        $admin = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $adminEmail]);
        $admin->setRoles(['ROLE_ADMIN']);
        $em->flush();

        // 2. Login pour récupérer le token
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $adminEmail,
            'password' => $password,
        ]));
        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        // 3. Créer un nouvel utilisateur en tant qu'admin
        $newUserEmail = 'new_user_' . uniqid() . '@example.com';

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token
        ], json_encode([
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => $newUserEmail,
            'password' => 'UserPass123!'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Utilisateur créé avec succès', $data['message']);

    }





}
