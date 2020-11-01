<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entity\User;

interface MessengerInterface
{
    public function sendMessage($userId, string $message);

    public function sendMessageToChat($chat_id, string $message);

    public function createChat($title, array $users);

    public function removeChatUser($chat_id, $user_id);

    public function getInviteLink($chat_id);

    public function isMessagesFromGroupAllowed($userId);
}
