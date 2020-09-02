<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener extends LogoutEvent
{
    public function getResponse(): ?Response
    {
        return new JsonResponse(['response' => 'OK']);
    }
}
