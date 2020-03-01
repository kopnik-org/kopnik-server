<?php

declare(strict_types=1);

namespace VK\Actions;

class Messages
{
    public function createChat($access_token, array $params = [])
    {
        return rand(100, 200);
    }

    public function getInviteLink($access_token, array $params = [])
    {
        return '//invite/chat/link';
    }

    public function send($access_token, array $params = [])
    {

    }

    public function isMessagesFromGroupAllowed($access_token, array $params = [])
    {
        return ['is_allowed' => 1];
    }
}
