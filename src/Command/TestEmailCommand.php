<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:test-email')]
class TestEmailCommand extends Command
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('ton-email@example.com')
            ->to('destinataire@example.com')
            ->subject('Test Symfony Mailer via Console')
            ->text('Ceci est un test d’email envoyé depuis la console Symfony.');

        $this->mailer->send($email);
        $output->writeln('✅ Email envoyé avec succès !');

        return Command::SUCCESS;
    }
}
