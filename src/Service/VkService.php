<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\VkException;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

class VkService
{
    protected $vkCallbackApiAccessToken;
    protected $vkCommunityId;
    protected $vk;

    public function __construct($vkCallbackApiAccessToken, $vkCommunityId)
    {
        $this->vk = new VKApiClient();
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
}
