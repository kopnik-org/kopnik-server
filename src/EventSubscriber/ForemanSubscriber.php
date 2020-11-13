<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Contracts\MessengerInterface;
use App\Entity\User;
use App\Event\UserEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use VK\Exceptions\Api\VKApiMessagesChatNotAdminException;
use VK\Exceptions\Api\VKApiMessagesChatUserNotInChatException;
use VK\Exceptions\Api\VKApiMessagesContactNotFoundException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

class ForemanSubscriber implements EventSubscriberInterface
{
    protected EntityManagerInterface $em;
    protected MessengerInterface $vk;

    public function __construct(EntityManagerInterface $em, MessengerInterface $vk)
    {
        $this->em = $em;
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
            $this->sendNotifyToForemanReset($user); // Исключение из десятки, если уже состоит

            // @todo обработка исключений от вк
            $this->vk->sendMessage($user->getForemanRequest()->getVkIdentifier(), sprintf('%s подал заявку на вступление в десятку', (string) $user));
        }
    }

    public function sendNotifyToForemanConfirm(User $user): void
    {
        $foreman = $user->getForeman();

        if (!$foreman) {
            return;
        }

        if ($foreman->getTenChatInviteLink()) {
            $ten_chat_link = $foreman->getTenChatInviteLink();
        } else {
            // 1) Создать групповой чат с заверителем и новобранцем
            $chat_id = $this->vk->createChat("Десятка {$foreman}",
                [$user->getVkIdentifier(), $foreman->getVkIdentifier()]
            );

            // 2) Получить ссылку приглашения в чат
            $ten_chat_link = $this->vk->getInviteLink($chat_id);

            // 3) Сохранить чат десятки старшины
            $foreman
                ->setTenChatId($chat_id)
                ->setTenChatInviteLink($ten_chat_link)
            ;

            $this->em->flush();

            $this->vk->sendMessage($foreman->getVkIdentifier(), "Создан твой чат старшины десятки $ten_chat_link");
        }

        $this->vk->sendMessage($user->getVkIdentifier(), "Старшина одобрил твою заявку. Перейти в чат десятки $ten_chat_link");
    }

    public function sendNotifyToForemanDecline(User $user): void
    {
        $this->vk->sendMessage($user->getVkIdentifier(), 'Старшина отклонил твою заявку');
    }

    public function sendNotifyToForemanReset(User $user): void
    {
        $foreman = $user->getForeman();

        if ($foreman) {
            $this->removeUserFromTenChat($user);

            $this->vk->sendMessage($foreman->getVkIdentifier(), sprintf('%s вышел из десятки', (string) $user));
        }
    }

    public function sendNotifyToSubordinateReset(User $user): void
    {
        $foreman = $user->getForeman();

        if ($foreman) {
            $this->removeUserFromTenChat($user);

            $this->vk->sendMessage($user->getVkIdentifier(), sprintf('Старшина %s исключил тебя из подчинённых', (string) $user->getForeman()));
        }
    }

    protected function removeUserFromTenChat(User $user)
    {
        try {
            $this->vk->removeChatUser($user->getForeman()->getTenChatId(), $user->getVkIdentifier());
        } catch (VKApiMessagesChatUserNotInChatException $e) {
            // User not found in chat
        } catch (VKApiMessagesContactNotFoundException $e) {
            // Contact not found
        } catch (VKApiMessagesChatNotAdminException $e) {
            // You are not admin of this chat
        } catch (VKApiException $e) {
            //
        } catch (VKClientException $e) {
            //
        }
    }
}
