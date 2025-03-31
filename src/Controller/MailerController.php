<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MailerController extends AbstractController
{
    #[Route('/api/send-email', name: 'api_send_email', methods: ['POST'])]
    public function sendEmailApi(MailerInterface $mailer): JsonResponse
    {
        $email = (new Email())
            ->from('ton-email@gmail.com')
            ->to('destinataire@example.com')
            ->subject('Test Symfony Mailer via API')
            ->text('Ceci est un test d’email envoyé via une requête API.');

        $mailer->send($email);

        return new JsonResponse(['message' => 'Email envoyé avec succès !'], Response::HTTP_OK);
    }
}
