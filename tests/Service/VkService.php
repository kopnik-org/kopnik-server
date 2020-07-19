<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;

class VkService
{
    public function sendMessage(User $user, string $message)
    {
        return;
    }

    public function createChat(User $user, User $witness)
    {
        return rand(100, 200);
    }

    public function getInviteLink($chat_id)
    {
        return ['link' => '//test/invite/chat/link'];
    }

    public function isMessagesFromGroupAllowed(User $user)
    {
        return ['is_allowed' => true];
    }
}
