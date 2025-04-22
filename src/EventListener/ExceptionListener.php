<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'kernel.exception')]
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event) : void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException) {
            $response = new JsonResponse([
                'status' => 'error',
                'message' => "Vous n'avez pas les droits pour accéder à cette ressource."
            ], 403);

            $event->setResponse($response);
        }
        if ($exception instanceof NotFoundHttpException) {
            $response = new JsonResponse([
                'status' => 'error',
                'message' => "Page non trouvée",
                'code' => 'ticket_not_found'

            ], 404);
            $event->setResponse($response);
        }
    }

}
