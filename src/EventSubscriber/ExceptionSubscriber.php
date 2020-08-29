<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'exception',
        ];
    }

    public function exception(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        $data = [
            'code'     => $e->getCode(),
            'message'  => $e->getMessage(),
            'file'     => $e->getFile(),
            'line'     => $e->getLine(),
            'trace'    => $e->getTrace(),
            'previous' => $e->getPrevious(),
        ];

        $event->setResponse(new JsonResponse(['error' => $data]));
    }
}
