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
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    const ERROR_UNAUTHORIZED    = 401; // No authentication
    const ERROR_NOT_VALID       = 2; // Not valid
    const ERROR_NO_WITNESS      = 3; // В системе отсутствуют заверители
    const ERROR_ACCESS_DENIED   = 4; // Доступ запрещен
    const ERROR_NO_PENDING      = 5; // Pending user not found

    /** @var User */
    // для сериалайзера
    protected $user;

    /**
     * Обновить своего (текущего) пользователя. Меняет статус пользователя на ОЖИДАЕТ ЗАВЕРЕНИЯ.
     *
     * @Route("/users/update", methods={"POST"}, name="api_users_update")
     */
    public function usersUpdate(Request $request, KernelInterface $kernel, EntityManagerInterface $em, $vkCallbackApiAccessToken, $vkCommunityId): JsonResponse
    {
        $user = $this->getUser();
        $this->user = $user;

        if (empty($this->getUser())) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_UNAUTHORIZED,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $response = [];

        $input = json_decode($request->getContent(), true);
        $data = [
            'first_name'    => $input['firstName'] ?? '',
            'patronymic'    => $input['patronymic'] ?? '',
            'last_name'     => $input['lastName'] ?? null,
            'birth_year'    => $input['birthyear'] ?? null,
            'passport_code' => $input['passport'] ?? null,
            'latitude'      => $input['location']['lat'] ?? null,
            'longitude'     => $input['location']['lng'] ?? null,
            'locale'        => $input['locale'] ?? null,
            'role'          => $input['role'] ? (int) $input['role'] : 0,
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
                    'error_code' => self::ERROR_NO_WITNESS,
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

                if ($user->getAssuranceChatInviteLink()) {
                    $invite_chat_link = $user->getAssuranceChatInviteLink();
                } else {
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
                }

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

                // 5) Сохранить ссылку-приглашение в профиле юзера
                $user->setAssuranceChatInviteLink($invite_chat_link);

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
                    'error_code' => self::ERROR_NOT_VALID,
                    'error_msg'  => 'Not valid',
                    'validation_errors' => $errors,
                    'request_params' => '@todo ',
                ]
            ]);
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * Получить заявки на регистрацию, которые должен заверять текущий пользователь, с атрибутом "заверитель".
     *
     * @Route("/users/pending", methods={"GET"}, name="api_users_pending")
     */
    public function usersPending(EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->getUser())) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_UNAUTHORIZED,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        if (!$this->user->isWitness()) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 403,
                    'error_msg'  => 'ACCESS_DENIED: Получить заявки могут только заверители',
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
                $response[] = $this->serializeUser($user, true);
            }
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * Подтвердить/отклонить заявку на регистрацию.
     *
     * @Route("/users/pending/update", methods={"POST"}, name="api_users_pending_update")
     */
    public function usersPendingUpdate(Request $request, EntityManagerInterface $em, $vkCallbackApiAccessToken): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->getUser())) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_UNAUTHORIZED,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        if (!$this->user->isWitness()) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 403,
                    'error_msg'  => 'ACCESS_DENIED: заявку могут обрабатывать только заверители',
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

        if ($data['status'] !== User::STATUS_CONFIRMED and $data['status'] !== User::STATUS_DECLINE) {
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
            if ($userPending->getWitness()->getId() !== $this->user->getId()) {
                return new JsonResponse([
                    'error' => [
                        'error_code' => 403,
                        'error_msg'  => 'Обработать можно только юзера у которого вы являетесь заверителем',
                        'request_params' => '@todo ',
                    ]
                ]);
            }

            $userPending->setStatus($data['status']);
            $em->flush();

            // Отправка сообщения в вк
            try {
                $message = 'Неверное присвоение статуса - обратитесь к разработчикам kopnik.org для разрешения проблеммы.';

                if ($userPending->getStatus() == User::STATUS_DECLINE) {
                    $message = 'Заявка на вступление в kopnik.org отклонена.';
                }

                if ($userPending->getStatus() == User::STATUS_CONFIRMED) {
                    $message = 'Заявка на вступление в kopnik.org одобрена.';
                }

                $vk = new VKApiClient();
                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $userPending->getVkIdentifier(),
                    'message' => $message,
                    'random_id' => random_int(100, 999999999),
                ]);
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

            $response = true;
        } else {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_NO_PENDING,
                    'error_msg'  => 'Pending user not found',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * @Route("/users/isMessagesFromGroupAllowed", methods={"GET"}, name="api_users_is_messages_from_group_allowed")
     */
    public function isMessagesFromGroupAllowed(EntityManagerInterface $em, $vkCommunityId, $vkCallbackApiAccessToken): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_UNAUTHORIZED,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $response = [];

        try {
            $vk = new VKApiClient();

            $result = $vk->messages()->isMessagesFromGroupAllowed($vkCallbackApiAccessToken, [
                'user_id'  => $this->user->getVkIdentifier(),
                'group_id' => $vkCommunityId,
            ]);

            if (isset($result['is_allowed'])) {
                $response['response'] = $result['is_allowed'] ? true : false;
            } else {
                $response['response'] = $result->is_allowed ? true : false;
            }

            return new JsonResponse($response);

            /*
            $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                'user_id' => $this->user->getVkIdentifier(),
                // 'domain' => 'some_user_name',
                'message' => "Проверка приёма сообщений от сообщества Kopnik.org в VK",
                'random_id' => random_int(100, 999999999),
            ]);

            $response = true;
            */
            //$response['is_messages_from_group_allowed'] = true;
        } catch (VKApiFloodException $e) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1000000 + $e->getErrorCode(),
                    'error_msg'  => $e->getMessage(),
                    'request_params' => '@todo',
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
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * @deprecated
     *
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
     * Получить нескольких пользовалетей.
     *
     * Если параметр ids не задан, будет подставлен идентификатор текущего пользователя из сессии.
     *
     * @Route("/users/get", methods={"GET"}, name="api_users_get")
     */
    public function usersGet(Request $request, UserRepository $ur): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_UNAUTHORIZED,
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
                    'error_code' => self::ERROR_UNAUTHORIZED,
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
     * Получить пользовалетей в заданном квадрате координат.
     *
     * @Route("/users/getTopInsideSquare", methods={"GET"}, name="api_users_get_top_inside_square")
     */
    public function usersGetTopInsideSquare(Request $request, UserRepository $ur): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_UNAUTHORIZED,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $count = $request->query->get('count', 50);
        $x1 = $request->query->get('x1');
        $x2 = $request->query->get('x2');
        $y1 = $request->query->get('y1');
        $y2 = $request->query->get('y2');

        $response = [];

        if (is_null($x1) or is_null($x2) or is_null($y1) or is_null($y2) or is_null($count)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 403,
                    'error_msg'  => 'Invalid input params',
                    'request_params' => '@todo ',
                ]
            ]);
        } else {
            foreach ($ur->findByCoordinates($x1, $y1, $x2, $y2, $count) as $user) {
                $response[] = $this->serializeUser($user);
            }
        }

        return new JsonResponse(['response' => $response]);
    }

    /**
     * Изменение только локали юзера.
     *
     * @Route("/users/updateLocale", methods={"POST"}, name="api_users_update_locale")
     */
    public function usersUpdateLocale(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_UNAUTHORIZED,
                    'error_msg'  => 'No authentication',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $input = json_decode($request->getContent(), true);
        $locale = $input['locale'] ?? null;

        // @todo сделать список поддерживаемых локалей.
        $locales = ['en', 'ru'];

        if (!in_array($locale, $locales)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => self::ERROR_NOT_VALID,
                    'error_msg'  => 'Не поддерживаемая локаль',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $this->user->setLocale($locale);

        $em->persist($this->user);
        $em->flush();

        $response = $this->serializeUser($this->user);

        return new JsonResponse(['response' => $response]);
    }

    /**
     * @param User $user
     * @param bool $forcePassport
     *
     * @return array
     *
     * @todo вынести в сервис
     */
    protected function serializeUser(User $user, bool $forcePassport = false): array
    {
        $location = new \stdClass();
        $location->lat = $user->getLatitude();
        $location->lng = $user->getLongitude();

        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'patronymic' => $user->getPatronymic(),
            'locale' => $user->getLocale(),
            'foreman_id' => $user->getForeman() ? $user->getForeman()->getId() : null,
            'witness_id' => $user->getWitness() ? $user->getWitness()->getId() : null,
            'birthyear' => $user->getBirthYear(),
            'location' => $location,
            'role' => $user->getRole(),
            'status' => $user->getStatus(),
            'passport' => ($this->user->getId() == $user->getId() or $forcePassport) ? $user->getPassportCode() : null,
            'photo' => $user->getPhoto(),
            'smallPhoto' => $user->getPhoto(),
        ];
    }
}
