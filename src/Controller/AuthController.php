<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class AuthController extends AbstractController
{
#[Route('/api/login', name: 'api_login', methods: ['POST'])]
public function login(): JsonResponse
{
$user = $this->getUser();
return $this->json([
'email' => $user->getUserIdentifier(),
'roles' => $user->getRoles(),
]);
}
}
