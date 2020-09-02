<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    public function onLogoutSuccess(LogoutEvent $event)
    {
        $event->setResponse(new JsonResponse(['response' => 'OK']));
    }
}
