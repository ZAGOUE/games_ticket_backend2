<?php

namespace App\Tests\Entity;


use App\Entity\AdminLog;
use PHPUnit\Framework\TestCase;

class AdminLogTest extends TestCase
{
    public function testAdminLogEntity()
    {
        $log = new AdminLog();
        $log->setAction('Connexion admin');
        $log->setCreatedAt(new \DateTimeImmutable('2025-04-21 14:00:00'));

        $this->assertSame('Connexion admin', $log->getAction());
        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getCreatedAt());
        $this->assertSame('2025-04-21 14:00:00', $log->getCreatedAt()->format('Y-m-d H:i:s'));
    }
    public function testAdminLogAccessors(): void
    {
        $log = new AdminLog();

        $id = 42;
        $action = "Connexion à l’espace admin";
        $date = new \DateTimeImmutable('2025-04-21 22:47:00');

        $log->setId($id);
        $log->setAction($action);
        $log->setCreatedAt($date);

        $this->assertSame($id, $log->getId());
        $this->assertSame($action, $log->getAction());
        $this->assertSame($date, $log->getCreatedAt());
    }

}
