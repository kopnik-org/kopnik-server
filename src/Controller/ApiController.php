<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserOauth;
use App\Form\Type\UserFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Flex\Response;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /** @var User */
    // для сериалайзера
    protected $user;

    /**
     * @Route("/users/update", methods={"POST"}, name="api_users_update")
     */
    public function usersUpdate(Request $request, KernelInterface $kernel, EntityManagerInterface $em, $vkCallbackApiAccessToken, $vkCommunityId): JsonResponse
    {
        $user = $this->getUser();
        $this->user = $user;

        if (empty($user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $response = [];

        $input = json_decode($request->getContent(), true);
        $data = [
            'first_name'    => $input['firstName'],
            'patronymic'    => $input['patronymic'],
            'last_name'     => $input['lastName'],
            'birth_year'    => $input['birthyear'],
            'passport_code' => $input['passport'],
            'latitude'      => $input['location'][0],
            'longitude'     => $input['location'][1],
//            'photo'         => $input['photo'],
//            'smallPhoto'    => $input['smallPhoto'],
            'update'        => ''
        ];

        if ($kernel->getEnvironment() == 'test') {
            $filename = $kernel->getCacheDir().'/_request_'.date('Y-m-d_H-i-s').'.log';

            file_put_contents($filename, print_r($request->getContent(), true) . "\n\n", FILE_APPEND);
            file_put_contents($filename, print_r($request->request->all(), true) . "\n\n", FILE_APPEND);
            file_put_contents($filename, print_r($data, true) . "\n\n", FILE_APPEND);
        }

        /*

        $data = [
            'firstname'     => $request->request->get('firstName'),
            'patronymic'    => $request->request->get('patronymic'),
            'lastname'      => $request->request->get('lastName'),
            'birth_year'    => $request->request->get('birthyear'),
            'passport_code' => $request->request->get('passport'),
            'latitude'      => $request->request->get('location')[0],
            'longitude'     => $request->request->get('location')[1],
//            'photo'         => $request->request->get('photo'),
//            'small_photo'    => $request->request->get('smallPhoto'),
            'update'        => '',
        ];
        */

//        $request2form = new Request();
//        $request2form->request->set('user', $data);
        $request->request->set('user', $data);
        $form = $this->createForm(UserFormType::class, $user, [
            'csrf_protection' => false,
            //'error_bubbling'  => false,
        ]);

        $form->handleRequest($request);
//        $form->handleRequest($request2form);

        // @todo Пока так находит первого и единственного заверителя
        $witness = $em->getRepository(User::class)->findOneBy(['is_witness' => true]);

        if (empty($witness)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 3,
                    'error_msg'  => 'В системе отсутствуют заверители',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        if ($form->isValid()) {
            try {
                $vk = new VKApiClient();
                /** @var User $user */
                $user = $this->getUser();

                if (empty($user->getWitness())) {
                    $user->setWitness($witness); // @todo костыль...
                }

                // 1) Создать групповой чат с заверителем и новобранцем
                $chat_id = $vk->messages()->createChat($vkCallbackApiAccessToken, [
                    'user_ids' => "{$user->getVkIdentifier()},{$witness->getVkIdentifier()}",
                    'title' => "{$user} - Заверение пользователя в Копнике",
                    'group_id' => $vkCommunityId,
                    //'v' => '5.103'
                ]);

                // 2) Получить ссылку приглашения в чат
                $invite_chat_link = $vk->messages()->getInviteLink($vkCallbackApiAccessToken, [
                    'peer_id' => 2000000000 + $chat_id,
                    'group_id' => $vkCommunityId,
                    'reset' => 0,
                ])['link'];

                // 3) Написать ссылку-приглашение в чат новобранцу
                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $user->getVkIdentifier(),
                    // 'domain' => 'some_user_name',
                    'message' => $user->getStatus() == User::STATUS_NEW ?
                        "Добро пожаловать в kopnik-org! Для заверения, пожалуйста, перейдите в чат по ссылке $invite_chat_link и договоритеcь о заверении аккаунта." :
                        "Повторная заявка на заверение в kopnik-org! Перейдите в чат по ссылке $invite_chat_link и договоритеcь о заверении аккаунта.",
                    'random_id' => random_int(100, 999999999),
                ]);

                // 4) Написать ссылку-приглашение в чат заверителю
                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $witness->getVkIdentifier(),
                    // 'domain' => 'some_user_name',
                    'message' => $user->getStatus() == User::STATUS_NEW ?
                        "Зарегистрировался новый пользователь {$user} ссылка на чат $invite_chat_link" :
                        "Повторная заявка на заверение нового пользователя {$user} ссылка на чат $invite_chat_link",
                    'random_id' => random_int(100, 999999999),
                ]);

                /*
                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $user->getVkIdentifier(),
                    'message' => "Повторная заявка на заверение в kopnik-org! Перейдите в чат по ссылке $invite_chat_link и договоритеcь о заверении аккаунта.",
                    'random_id' => random_int(100, 999999999),
                ]);

                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $user->getWitness()->getVkIdentifier(),
                    'message' => "Повторная заявка на заверение нового пользователя {$user} ссылка на чат $invite_chat_link",
                    'random_id' => random_int(100, 999999999),
                ]);
                */
            } catch (VKApiFloodException $e) {
                return new JsonResponse([
                    'error' => [
                        'error_code' => 1000000 + $e->getErrorCode(),
                        'error_msg'  => $e->getMessage(),
                        'request_params' => '@todo ',
                    ]
                ]);
            } catch (VKApiException $e) {
                return new JsonResponse([
                    'error' => [
                        'error_code' => 1000000 + $e->getErrorCode(),
                        'error_msg'  => $e->getMessage(),
                        'request_params' => '@todo',
                    ]
                ]);
            } catch (VKClientException $e) {
                return new JsonResponse([
                    'error' => [
                        'error_code' => 1000000 + $e->getErrorCode(),
                        'error_msg'  => $e->getMessage(),
                        'request_params' => '@todo',
                    ]
                ]);
            }

            $user->setStatus(User::STATUS_PENDING);

            $em->persist($user);
            $em->flush();

            $response[] = $this->serializeUser($witness);
        } else {
            $errors = [];
            foreach ($form->getErrors() as $error) {
                $errors[] = [
                    'field' => $error->getOrigin()->getExtraData(),
                    'message' => $error->getMessage(),
                ];
            }

            return new JsonResponse([
                'error' => [
                    'error_code' => 2,
                    'error_msg'  => 'Not valid',
                    'validation_errors' => $errors,
                    'request_params' => '@todo ',
                ]
            ]);
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * @Route("/users/pending", methods={"GET"}, name="api_users_pending")
     */
    public function usersPending(EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $response = [];

        $users = $em->getRepository(User::class)->findBy([
            'status'  => User::STATUS_PENDING,
            'witness' => $this->user->getId(),
        ]);

        if ($users) {
            foreach ($users as $user) {
                $response[] = $this->serializeUser($user);
            }
        } else {
            return new JsonResponse([
                'error' => [
                    'error_code' => 5,
                    'error_msg'  => 'Pending users not found',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * @Route("/users/pending/update", methods={"POST"}, name="api_users_pending_update")
     */
    public function usersPendingUpdate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $response = [];

        // @todo проверку наличия входных данных
        $input = json_decode($request->getContent(), true);
        $data = [
            'id'     => (int) $input['id'],
            'status' => (int) $input['status'],
        ];

        if ($data['status'] !== User::STATUS_CONFIRMED or $data['status'] !== User::STATUS_DECLINE) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1,
                    'error_msg'  => 'Not accesible status for pending',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $userPending = $em->getRepository(User::class)->findOneBy([
            'id'      => $data['id'],
            'status'  => User::STATUS_PENDING,
            'witness' => $this->user->getId(),
        ]);

        if ($userPending) {
            $userPending->setStatus($data['status']);
            $em->flush();

            $response = true;
        } else {
            return new JsonResponse([
                'error' => [
                    'error_code' => 5,
                    'error_msg'  => 'Pending user not found',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        return new JsonResponse(['response' => $response]);
    }


    /**
     * @Route("/users/witness_request", methods={"POST"}, name="api_users_witness_request")
     */
    public function usersWitnessRequest(Request $request, LoggerInterface $logger): JsonResponse
    {
        $logger->alert($request, ['TEST1']);

        return new JsonResponse([
            'CONTENT' => $request->getContent(),
            'GET' => $request->query->all(),
            'POST' => $request->request->all(),
        ]);
    }

    /**
     * @Route("/users/get", methods={"GET"}, name="api_users_get")
     */
    public function usersGet(Request $request, UserRepository $ur): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $ids = $request->query->get('ids');
        $response = [];

        if (empty($ids)) {
            $response[] = $this->serializeUser($this->user);
        } else {
            foreach (explode(',', $ids) as $id) {
                $user = $ur->find($id);

                if (empty($user)) {
                    return new JsonResponse([
                        'error' => [
                            'error_code' => 1,
                            'error_msg'  => 'Invalid user ids',
                            'request_params' => '@todo ',
                        ]
                    ]);
                }

                $response[] = $this->serializeUser($user);
            }
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * @Route("/users/getByUid", name="api_users_get_by_uid", methods={"GET"})
     */
    public function usersGetByUid(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $userOauth = $em->getRepository(UserOauth::class)->findOneBy(['identifier' => $request->query->get('uid')]);

        if ($userOauth) {
            $data = [
                'response' => $this->serializeUser($userOauth->getUser()),
            ];
        } else {
            return new JsonResponse([
                'error' => [
                    'error_code' => 2,
                    'error_msg'  => 'User not found',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        return new JsonResponse($data);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    protected function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'patronymic' => $user->getPatronymic(),
            'foreman_id' => $user->getForeman() ? $user->getForeman()->getId() : null,
            'witness_id' => $user->getWitness() ? $user->getWitness()->getId() : null,
            'birthyear' => $user->getBirthYear(),
            'location' => [$user->getLatitude(), $user->getLongitude()],
            'status' => $user->getStatus(),
            'passport' => $this->user->getId() == $user->getId() ? $user->getPassportCode() : null, // только свой
            'photo' => '@todo',
            'smallPhoto' => '@todo',
        ];
    }
}
