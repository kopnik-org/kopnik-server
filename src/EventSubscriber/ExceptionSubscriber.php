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
        $event->allowCustomResponseCode();

        $e = $event->getThrowable();

        $data = [
            'error_code'  => 500,
            'error_msg'   => $e->getMessage(),
            'error_file'  => $e->getFile(),
            'error_line'  => $e->getLine(),
            'error_trace' => $e->getTrace(),
//            'previous' => $e->getPrevious(),
        ];

        $event->setResponse(new JsonResponse(['error' => $data], 200));
    }
}
