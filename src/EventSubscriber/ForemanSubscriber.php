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
            UserEvent::FOREMAN_REQUEST => [
                ['sendNotifyToForemanRequest', 0],
                ['removeUserFromTenChat', 0],
            ],
            UserEvent::FOREMAN_CONFIRM_BEFORE_CHANGE => [
                ['removeUserFromTenChat', 0], // @todo похоже не нужно
            ],
            UserEvent::FOREMAN_CONFIRM_AFTER_CHANGE => [
                ['sendNotifyToForemanConfirm', 0],
            ],
            UserEvent::FOREMAN_DECLINE => [
                ['sendNotifyToForemanDecline', 0],
            ],
            UserEvent::FOREMAN_RESET => [
                ['sendNotifyToForemanReset', 0],
                ['removeUserFromTenChat', 0],
            ],
            UserEvent::SUBORDINATE_RESET => [
                ['sendNotifyToSubordinateReset', 0],
                ['removeUserFromTenChat', 0],
            ],
        ];
    }

    public function sendNotifyToForemanRequest(User $user): void
    {
        if ($user->getForemanRequest()) {
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
            $this->vk->sendMessage($foreman->getVkIdentifier(), sprintf('%s вышел из десятки', (string) $user));
        }
    }

    public function sendNotifyToSubordinateReset(User $user): void
    {
        $foreman = $user->getForeman();

        if ($foreman) {
            $this->vk->sendMessage($user->getVkIdentifier(), sprintf('Старшина %s исключил тебя из подчинённых', (string) $foreman));
        }
    }

    /**
     * Удалить из чата десятки своего старшины.
     */
    public function removeUserFromTenChat(User $user): void
    {
        if (!$user->getForeman()) {
            return;
        }

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
