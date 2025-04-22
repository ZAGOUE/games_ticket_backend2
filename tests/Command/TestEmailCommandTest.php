<?php

// tests/Command/TestEmailCommandTest.php
namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class TestEmailCommandTest extends KernelTestCase
{
    public function testCommandOutput(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        // ✅ Correctement accédé via getContainer()
        $command = self::getContainer()->get(\App\Command\TestEmailCommand::class);
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:test-email'));
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Email envoyé avec succès', $output);


    }
}

