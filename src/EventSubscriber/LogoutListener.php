<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutSuccess',
        ];
    }

    public function onLogoutSuccess(LogoutEvent $event)
    {
        $event->setResponse(new JsonResponse(['response' => 'OK']));
    }
}
