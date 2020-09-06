<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\AbstractApiController;
use App\Entity\User;
use App\Entity\UserOauth;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\Api\VKApiMessagesContactNotFoundException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;
use VK\TransportClient\TransportRequestException;

class DefaultController extends AbstractApiController
{
    /**
     * @Route("/api/test/setupDB", name="test_setup_db")
     */
    public function setupDB(KernelInterface $kernel): JsonResponse
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
            '--full-database' => true,
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ]);
        $application->run($input, $output);

        $input = new ArrayInput([
            'command' => 'hautelook:fixtures:load',
            '-q' => true,
        ]);
        $application->run($input, $output);

        return $this->jsonResponse(['output' => $output->fetch()]);
    }

    /**
     * @Route("/api/test/createUser", methods={"POST"}, name="test_create_user")
     */
    public function createUser(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $input = json_decode($request->getContent(), true);

        $data = [
            'first_name'    => $input['firstName'] ?? '',
            'last_name'     => $input['lastName'] ?? null,
            'is_witness'    => $input['isWitness'] ?? false,
            'witness_id'    => $input['witness_id'] ?? null,
            'patronymic'    => $input['patronymic'] ?? '',
            'birth_year'    => $input['birthyear'] ?? null,
            'passport_code' => $input['passport'] ?? null,
            'latitude'      => $input['location']['lat'] ?? null,
            'longitude'     => $input['location']['lng'] ?? null,
            'locale'        => $input['locale'] ?? null,
            'role'          => $input['role'] ? (int) $input['role'] : User::ROLE_STRANGER,
            'status'        => $input['status'] ? (int) $input['status'] : User::STATUS_NEW,
            // oAuth
            'identifier'    => $input['identifier'],
            'email'         => $input['email'],
            'access_token'  => $input['access_token'],
            // only test
            'photo'         => $input['photo'] ?? null,
            'smallPhoto'    => $input['smallPhoto'] ?? null,
            'foreman_id'    => $input['foreman_id'] ?? null,
            'foremanRequest_id' => $input['foremanRequest_id'] ?? null,
        ];

        try {
            $userOauth = $em->getRepository(UserOauth::class)->findOneBy([
                'email' => $data['email'],
                'identifier' => $data['identifier'],
                'access_token' => $data['access_token'],
                'provider' => 'vkontakte',
            ]);
        } catch (DriverException $e) {
            return $this->jsonError($e->getCode(), $e->getMessage());
        }

        if ($userOauth) {
            return $this->jsonError(self::ERROR_NOT_VALID, 'Такой юзер уже зареган');
        }

        $user = new User();
        $user
            ->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setPatronymic($data['patronymic'])
            ->setPassportCode($data['passport_code'])
            ->setBirthYear($data['birth_year'])
            ->setLatitude($data['latitude'])
            ->setLongitude($data['longitude'])
            ->setLocale($data['locale'])
            ->setIsWitness((bool) $data['is_witness'])
            ->setKopnikRole($data['role'])
            ->setStatus($data['status'])
            ->setPhoto($data['photo'])
            ->setSmallPhoto($data['smallPhoto'])
        ;

        if ($data['witness_id']) {
            $witness = $em->getRepository(User::class)->find((int) $data['witness_id']);

            if ( ! $witness) {
                return $this->jsonError(self::ERROR_NOT_VALID, 'Указан не существующий witness_id');
            }

            $user->setWitness($witness);
        }

        if ($data['foreman_id']) {
            $foreman = $em->getRepository(User::class)->find((int) $data['foreman_id']);

            if ( ! $foreman) {
                return $this->jsonError(self::ERROR_NOT_VALID, 'Указан не существующий foreman_id');
            }

            $user->setForeman($foreman);
        }

        if ($data['foremanRequest_id']) {
            $foreman = $em->getRepository(User::class)->find((int) $data['foremanRequest_id']);

            if ( ! $foreman) {
                return $this->jsonError(self::ERROR_NOT_VALID, 'Указан не существующий foremanRequest_id');
            }

            $user->setForemanRequest($foreman);
        }

        $userOauth = new UserOauth();
        $userOauth
            ->setEmail($data['email'])
            ->setAccessToken($data['access_token'])
            ->setIdentifier($data['identifier'])
            ->setProvider('vkontakte')
            ->setUser($user)
        ;

        try {
            $em->persist($userOauth);
            $em->persist($user);
            $em->flush();
        } catch (DriverException $e) {
            return $this->jsonError($e->getCode(), $e->getMessage());
        }

        return $this->jsonResponse($user->getId());
    }

    /**
     * @Route("/api/test/sendVkMessage", methods={"GET"}, name="test_send_vk_message")
     */
    public function sendVkMessage($testVkUserId, ContainerInterface $container): JsonResponse
    {
        $vk = $container->get('test_vk_service');

        if (empty($testVkUserId)) {
            return $this->jsonError(400, 'В .env.test.local не задано TEST_VK_USER_ID');
        }

        try {
            $result = $vk->sendMessage($testVkUserId, 'Test OK');
        } catch (VKApiFloodException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKApiException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKClientException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (TransportRequestException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKApiMessagesContactNotFoundException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        }

        return $this->jsonResponse($result);
    }

    /**
     * @Route("/api/test/createVkChat", methods={"GET"}, name="test_create_vk_chat")
     */
    public function createVkChat($testVkUserId, ContainerInterface $container): JsonResponse
    {
        $vk = $container->get('test_vk_service');

        if (empty($testVkUserId)) {
            return $this->jsonError(400, 'В .env.test.local не задано TEST_VK_USER_ID');
        }

        try {
            $date = (new \DateTime())->format('d.m.Y H:i:s');
            $chat_id = $vk->createChat('Тестовый чат ' . $date, [$testVkUserId]);
            $invite_chat_link = $vk->getInviteLink($chat_id);
            $msg_id = $vk->sendMessage($testVkUserId, "Тестовая проверка создания чата. Ссылка на чат $invite_chat_link");
//            $add_to_chat = $vk->addChatUser($chat_id, $testVkUserId);
//            $join_chat = $vk->joinChatByInviteLink($invite_chat_link);
            $chat_msg_id = $vk->sendMessageToChat($chat_id, 'Тестовое сообщение от бота');

            $result = [
                'chat_id' => $chat_id,
                'invite_chat_link' => $invite_chat_link,
                'msg_id' => $msg_id,
//                'join_chat' => $join_chat,
//                'add_to_chat' => $add_to_chat,
                'chat_msg_id' => $chat_msg_id,
            ];
        } catch (VKApiFloodException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKApiException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKClientException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (TransportRequestException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKApiMessagesContactNotFoundException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        }

        return $this->jsonResponse($result);
    }

    /**
     * @Route("/api/bodyError")
     */
    public function bodyError(Request $request): Response
    {
        $input = json_decode($request->getContent(), true);

//        $pass1 = $input['number'] + 10;
        $pass1 = 10 / $input['number'];

//        if (!is_int($input['number'])) {
//            throw new \Exception('number must be integer');
//        }

        $pass2 = strlen($input['string']);

        foreach ($input['array'] as $key => $val) {
            $pass3 = $val;
        }

        foreach ($input['object'] as $key => $val) {
            $pass4 = $val;
        }

        return new JsonResponse($input);
    }
}
