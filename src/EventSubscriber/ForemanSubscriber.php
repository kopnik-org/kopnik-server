<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\UserEvent;
use App\Service\VkService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ForemanSubscriber implements EventSubscriberInterface
{
    protected $vk;

    public function __construct(VkService $vk)
    {
        $this->vk = $vk;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::FOREMAN_REQUEST   => 'sendNotifyToForemanRequest',
            UserEvent::FOREMAN_CONFIRM   => 'sendNotifyToForemanConfirm',
            UserEvent::FOREMAN_DECLINE   => 'sendNotifyToForemanDecline',
            UserEvent::FOREMAN_RESET     => 'sendNotifyToForemanReset',
            UserEvent::SUBORDINATE_RESET => 'sendNotifyToSubordinateReset',
        ];
    }

    public function sendNotifyToForemanRequest(User $user): void
    {
        if ($user->getForemanRequest()) {
            // @todo обработка исключений от вк
            $this->vk->sendMessage($user->getForemanRequest(), 'У вас новая заявка в старшины от ' . (string) $user);
        }
    }

    public function sendNotifyToForemanConfirm(User $user): void
    {
        $this->vk->sendMessage($user, 'Ваша заявка на старшину принята, ваш старшина ' . (string) $user->getForeman());
    }

    public function sendNotifyToForemanDecline(User $user): void
    {
        $this->vk->sendMessage($user, 'Ваша заявка на старшину отклонена');
    }

    public function sendNotifyToForemanReset(User $user): void
    {
        $foreman = $user->getForeman();

        if ($foreman) {
            $this->vk->sendMessage($foreman, sprintf('Пользователь %s исключил вас из старшины', (string) $user));
        }
    }

    public function sendNotifyToSubordinateReset(User $user): void
    {
        $foreman = $user->getForeman();

        if ($foreman) {
            $this->vk->sendMessage($user, sprintf('Старшина %s исключил вас из подчинённых', (string) $user->getForeman()));
        }
    }
}
