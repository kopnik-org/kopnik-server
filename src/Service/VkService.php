<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\MessengerInterface;
use App\Exception\VkException;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

/**
 * @todo VK\TransportClient\TransportRequestException
 */
class VkService implements MessengerInterface
{
    protected $vkCallbackApiAccessToken;
    protected $vkCommunityId;
    protected VKApiClient $vk;

    public function __construct($vkCallbackApiAccessToken, $vkCommunityId)
    {
        $this->vk = new VKApiClient();
        $this->vkCallbackApiAccessToken = $vkCallbackApiAccessToken;
        $this->vkCommunityId = $vkCommunityId;
    }

    /**
     * @return mixed|void
     *
     * @throws VKApiException
     * @throws VKClientException
     * @throws \VK\Exceptions\Api\VKApiMessagesCantFwdException
     * @throws \VK\Exceptions\Api\VKApiMessagesChatBotFeatureException
     * @throws \VK\Exceptions\Api\VKApiMessagesChatUserNoAccessException
     * @throws \VK\Exceptions\Api\VKApiMessagesContactNotFoundException
     * @throws \VK\Exceptions\Api\VKApiMessagesDenySendException
     * @throws \VK\Exceptions\Api\VKApiMessagesKeyboardInvalidException
     * @throws \VK\Exceptions\Api\VKApiMessagesPrivacyException
     * @throws \VK\Exceptions\Api\VKApiMessagesTooLongForwardsException
     * @throws \VK\Exceptions\Api\VKApiMessagesTooLongMessageException
     * @throws \VK\Exceptions\Api\VKApiMessagesTooManyPostsException
     * @throws \VK\Exceptions\Api\VKApiMessagesUserBlockedException
     */
    public function sendMessage($userId, string $message)
    {
        return $this->vk->messages()->send($this->vkCallbackApiAccessToken, [
            'user_id' => $userId,
            // 'domain' => 'some_user_name',
            'message' => $message,
            'random_id' => \random_int(100, 999999999),
        ]);

        /*
        try {
            return $this->vk->messages()->send($this->vkCallbackApiAccessToken, [
                'user_id' => $user->getVkIdentifier(),
                // 'domain' => 'some_user_name',
                'message' => $message,
                'random_id' => \random_int(100, 999999999),
            ]);
        } catch (VKApiFloodException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKApiException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKClientException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        }
        */
    }

    public function sendMessageToChat($chat_id, string $message)
    {
        return $this->vk->messages()->send($this->vkCallbackApiAccessToken, [
//            'chat_id'   => $chat_id,
            'peer_id'   => 2000000000 + $chat_id,
            'message'   => $message,
            'random_id' => \random_int(100, 999999999),
        ]);
    }

    public function createChat($title, array $users)
    {
        return $this->vk->messages()->createChat($this->vkCallbackApiAccessToken, [
            'title'    => $title,
            'user_ids' => implode(',', $users),
            'group_id' => $this->vkCommunityId,
            //'v' => '5.103'
        ]);
    }

    public function removeChatUser($chat_id, $user_id)
    {
        return $this->vk->messages()->removeChatUser($this->vkCallbackApiAccessToken, [
            'chat_id'  => $chat_id,
            'user_id'  => $user_id,
        ]);
    }

    /**
     * Не используется
     */
    public function addChatUser($chat_id, $user_id)
    {
        return $this->vk->messages()->addChatUser($this->vkCallbackApiAccessToken, [
            'chat_id'  => $chat_id,
            'user_id'  => $user_id,
            'visible_messages_count' => 100,
        ]);
    }

    public function getInviteLink($chat_id)
    {
        return $this->vk->messages()->getInviteLink($this->vkCallbackApiAccessToken, [
//            'chat_id'  => $chat_id,
            'peer_id'  => 2000000000 + $chat_id,
            'reset'    => 0,
            'group_id' => $this->vkCommunityId,
        ])['link'];
    }

    public function isMessagesFromGroupAllowed($userId)
    {
        return $this->vk->messages()->isMessagesFromGroupAllowed($this->vkCallbackApiAccessToken, [
            'user_id'  => $userId,
            'group_id' => $this->vkCommunityId,
        ]);
    }

    /**
     * Не используется
     */
    public function joinChatByInviteLink($link)
    {
        return $this->vk->messages()->joinChatByInviteLink($this->vkCallbackApiAccessToken, [
            'link'  => $link,
        ]);
    }

    public function getUser($userIds)
    {
        return $this->vk->users()->get($this->vkCallbackApiAccessToken, [
            'user_ids'  => $userIds,
            'fields' => 'photo_id,photo_200,photo_100,photo_400,sex,bdate',
        ]);
    }
}
