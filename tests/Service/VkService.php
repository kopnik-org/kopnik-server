<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Contracts\MessengerInterface;
use App\Entity\User;

class VkService implements MessengerInterface
{
    public function sendMessage($userId, string $message)
    {
        return;
    }

    public function sendMessageToChat($chat_id, string $message)
    {
        return;
    }

    public function createChat($title, array $users)
    {
        return rand(100, 200);
    }

    public function getInviteLink($chat_id)
    {
        //return ['link' => '//test/invite/chat/link'];
        return '//test/invite/chat/link';
    }

    public function isMessagesFromGroupAllowed($userId)
    {
        return ['is_allowed' => true];
    }
}
