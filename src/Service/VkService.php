<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\VkException;
use Symfony\Component\HttpKernel\KernelInterface;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

class VkService
{
    protected $isTestEnv;
    protected $vkCallbackApiAccessToken;
    protected $vkCommunityId;
    protected $vk;

    public function __construct(KernelInterface $kernel, $vkCallbackApiAccessToken, $vkCommunityId)
    {
        $this->isTestEnv = $kernel->getEnvironment() === 'test' ? true : false;

        $this->vk = new VKApiClient();
        $this->vkCallbackApiAccessToken = $vkCallbackApiAccessToken;
        $this->vkCommunityId = $vkCommunityId;
    }

    /**
     * @param User   $user
     * @param string $message
     *
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
    public function sendMessage(User $user, string $message)
    {
        if ($this->isTestEnv) {
            return;
        }

        return $this->vk->messages()->send($this->vkCallbackApiAccessToken, [
            'user_id' => $user->getVkIdentifier(),
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

    public function createChat(User $user, User $witness)
    {
        if ($this->isTestEnv) {
            return rand(100, 200);
        }

        return $this->vk->messages()->createChat($this->vkCallbackApiAccessToken, [
            'user_ids' => "{$user->getVkIdentifier()},{$witness->getVkIdentifier()}",
            'title' => "{$user} - Заверение пользователя в Копнике",
            'group_id' => $this->vkCommunityId,
            //'v' => '5.103'
        ]);
    }

    public function getInviteLink($chat_id)
    {
        if ($this->isTestEnv) {
            return ['link' => '//test/invite/chat/link'];
        }

        return $this->vk->messages()->getInviteLink($this->vkCallbackApiAccessToken, [
            'peer_id' => 2000000000 + $chat_id,
            'group_id' => $this->vkCommunityId,
            'reset' => 0,
        ])['link'];
    }

    public function isMessagesFromGroupAllowed(User $user)
    {
        if ($this->isTestEnv) {
            return ['is_allowed' => true];
        }

        return $this->vk->messages()->isMessagesFromGroupAllowed($this->vkCallbackApiAccessToken, [
            'user_id'  => $user->getVkIdentifier(),
            'group_id' => $this->vkCommunityId,
        ]);
    }
}
