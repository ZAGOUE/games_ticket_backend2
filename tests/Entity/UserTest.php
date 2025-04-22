<?php


namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserGettersAndSetters(): void
    {
        $user = new User();

        $user->setFirstName('Jean');
        $this->assertSame('Jean', $user->getFirstName());

        $user->setLastName('Dupont');
        $this->assertSame('Dupont', $user->getLastName());

        $user->setEmail('jean@example.com');
        $this->assertSame('jean@example.com', $user->getEmail());

        $user->setPassword('secure');
        $this->assertSame('secure', $user->getPassword());

        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }
}
