<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contracts\MessengerInterface;
use App\Entity\User;
use App\Entity\UserOauth;
use App\Form\Type\UserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;
use VK\TransportClient\TransportRequestException;

/**
 * @Route("/api/users")
 */
class ApiUsersController extends AbstractApiController
{
    /**
     * Расширенный метод запроса данных для уменьшения кол-ва запросов по хттп
     *
     * @Route("/getEx", methods={"GET"})
     */
    public function getEx(): JsonResponse
    {
        $user = $this->getUser();
        $this->user = $user;

        if (empty($this->getUser())) {
            return $this->jsonError(1000 + self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $response = json_decode($this->forward('App\Controller\ApiUsersController::usersGet')->getContent(),true)['response'][0];

        $response['subordinates'] = json_decode($this->forward('App\Controller\ApiUsersForemanController::getSubordinates')->getContent(),true)['response'];
        $response['foremanRequests'] = json_decode($this->forward('App\Controller\ApiUsersForemanController::getForemanRequests')->getContent(),true)['response'];

        $usersPending = json_decode($this->forward('App\Controller\ApiUsersController::usersPending')->getContent(),true);

        if (isset($usersPending['response'])) {
            $response['witnessRequests'] = $usersPending['response'];
        } elseif (isset($usersPending['error'])) {
            $response['witnessRequests'] = $usersPending['error'];
        } else {
            $response['witnessRequests'] = ['error' => 'unknown error'];
        }

        return $this->jsonResponse($response);
    }

    /**
     * Обновить своего (текущего) пользователя. Меняет статус пользователя на ОЖИДАЕТ ЗАВЕРЕНИЯ.
     *
     * @Route("/updateProfile", methods={"POST"}, name="api_users_update_profile")
     */
    public function updateProfile(
        Request $request,
        KernelInterface $kernel,
        EntityManagerInterface $em,
        MessengerInterface $vk
    ): JsonResponse {
        $user = $this->getUser();
        $this->user = $user;

        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
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

        // @todo Пока так находит первого и единственного заверителя
        $witness = $em->getRepository(User::class)->findOneBy(['is_witness' => true]);

        if (empty($witness)) {
            return $this->jsonError(self::ERROR_NO_WITNESS, 'В системе отсутствуют заверители');
        }

        if ($form->isValid()) {
            try {
                /** @var User $user */
                $user = $this->getUser();

                if (empty($user->getWitness())) {
                    $user->setWitness($witness); // @todo костыль...
                }

                if ($user->getAssuranceChatInviteLink()) {
                    $invite_chat_link = $user->getAssuranceChatInviteLink();
                } else {
                    // 1) Создать групповой чат с заверителем и новобранцем
                    $chat_id = $vk->createChat("{$user} - Заверение пользователя в Копнике",
                        [$user->getVkIdentifier(), $witness->getVkIdentifier()]
                    );
                    $user->setAssuranceChatId($chat_id);

                    // 2) Получить ссылку приглашения в чат
                    $invite_chat_link = $vk->getInviteLink($chat_id);
                }

                // 3) Написать ссылку-приглашение в чат новобранцу
                $message = $user->getStatus() == User::STATUS_NEW ?
                    "Добро пожаловать в kopnik-org! Для заверения, пожалуйста, перейдите в чат по ссылке $invite_chat_link и договоритеcь о заверении аккаунта." :
                    "Повторная заявка на заверение в kopnik-org! Перейти в чат по ссылке $invite_chat_link и договоритеcь о заверении аккаунта.";

                $result = $vk->sendMessage($user->getVkIdentifier(), $message);

                // 4) Написать ссылку-приглашение в чат заверителю
                $message = $user->getStatus() == User::STATUS_NEW ?
                    "Зарегистрировался новый пользователь {$user} ссылка на чат $invite_chat_link" :
                    "Повторная заявка на заверение нового пользователя {$user} ссылка на чат $invite_chat_link";

                $result = $vk->sendMessage($witness->getVkIdentifier(), $message);

                // 5) Сохранить ссылку-приглашение в профиле юзера
                $user->setAssuranceChatInviteLink($invite_chat_link);
            } catch (VKApiFloodException $e) {
                return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
            } catch (VKApiException $e) {
                return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
            } catch (VKClientException $e) {
                return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
            }

            if ( ! $user->isWitness()) {
                $user->setStatus(User::STATUS_PENDING);
            }

            try {
                $result = $vk->getUser($user->getVkIdentifier());
                $item = $result[0];
                $user
                    ->setPhoto($item['photo_400'] ?? null)
                    ->setSmallPhoto($item['photo_100'] ?? null)
                ;
            } catch (VKClientException $e) {
                // dummy
            } catch (TransportRequestException $e) {
                // dummy
            } catch (VKApiException $e) {
                // dummy
            }

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

            return $this->jsonErrorWithValidation(self::ERROR_NOT_VALID, 'Not valid', $errors, $request);
        }

        return $this->jsonResponse($response);
    }

    /**
     * Получить заявки на регистрацию, которые должен заверять текущий пользователь, с атрибутом "заверитель".
     *
     * @Route("/pending", methods={"GET"}, name="api_users_pending")
     */
    public function usersPending(EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        if (!$this->user->isWitness()) {
            return $this->jsonResponse([]);
            //return $this->jsonError(403, 'ACCESS_DENIED: Получить заявки могут только заверители');
        }

        $users = $em->getRepository(User::class)->findBy([
            'status'  => User::STATUS_PENDING,
            'witness' => $this->user->getId(),
        ]);

        $response = [];
        if ($users) {
            foreach ($users as $user) {
                $response[] = $this->serializeUser($user, true);
            }
        }

        return $this->jsonResponse($response);
    }

    /**
     * Подтвердить/отклонить заявку на регистрацию.
     *
     * @Route("/pending/update", methods={"POST"}, name="api_users_pending_update")
     */
    public function usersPendingUpdate(Request $request, EntityManagerInterface $em, MessengerInterface $vk): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        if (!$this->user->isWitness()) {
            return $this->jsonError(403, 'ACCESS_DENIED: Получить заявки могут только заверители');
        }

        // @todo проверку наличия входных данных
        $input = json_decode($request->getContent(), true);
        $data = [
            'id'     => (int) $input['id'],
            'status' => (int) $input['status'],
        ];

        if ($data['status'] !== User::STATUS_CONFIRMED and $data['status'] !== User::STATUS_DECLINE) {
            return $this->jsonError(self::ERROR_NO_PENDING, 'Not accesible status for pending');
        }

        $userPending = $em->getRepository(User::class)->findOneBy([
            'id'      => $data['id'],
            'status'  => User::STATUS_PENDING,
            'witness' => $this->user->getId(),
        ]);

        if ($userPending) {
            if ($userPending->getWitness()->getId() !== $this->user->getId()) {
                return $this->jsonError(403, 'Обработать можно только юзера у которого вы являетесь заверителем');
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

                $result = $vk->sendMessageToChat($userPending->getAssuranceChatId(), $message);
            } catch (VKApiFloodException $e) {
                return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
            } catch (VKApiException $e) {
                return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
            } catch (VKClientException $e) {
                return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
            }

            return $this->jsonResponse(true);
        } else {
            return $this->jsonError(self::ERROR_NO_PENDING, 'Pending user not found', $request);
        }
    }

    /**
     * @Route("/isMessagesFromGroupAllowed", methods={"GET"}, name="api_users_is_messages_from_group_allowed")
     */
    public function isMessagesFromGroupAllowed(MessengerInterface $vk): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        try {
            $result = $vk->isMessagesFromGroupAllowed($this->user->getVkIdentifier());

            if (isset($result['is_allowed'])) {
                $response = $result['is_allowed'] ? true : false;
            } else {
                $response = $result->is_allowed ? true : false;
            }

            return $this->jsonResponse($response);
        } catch (VKApiFloodException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        } catch (VKApiException $e) {
            return $this->jsonError(1000000 + $e->getErrorCode(), $e->getMessage());
        }
    }

    /**
     * Получить нескольких пользовалетей.
     *
     * Если параметр ids не задан, будет подставлен идентификатор текущего пользователя из сессии.
     *
     * @Route("/get", methods={"GET"}, name="api_users_get")
     */
    public function usersGet(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $ids = $request->query->get('ids');
        $response = [];

        if (empty($ids)) {
            $response[] = $this->serializeUser($this->user);
        } else {
            $ur = $em->getRepository(User::class);

            foreach (explode(',', $ids) as $id) {
                $user = $ur->find($id);

                if (empty($user)) {
                    return $this->jsonError(1, 'Invalid user ids');
                }

                $response[] = $this->serializeUser($user);
            }
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/getByUid", name="api_users_get_by_uid", methods={"GET"})
     */
    public function usersGetByUid(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $userOauth = $em->getRepository(UserOauth::class)->findOneBy(['identifier' => $request->query->get('uid')]);

        if ($userOauth) {
            $response = $this->serializeUser($userOauth->getUser());
        } else {
            return $this->jsonError(404, 'User not found');
        }

        return $this->jsonResponse($response);
    }

    /**
     * Получить пользовалетей в заданном квадрате координат.
     *
     * @Route("/getTopInsideSquare", methods={"GET"}, name="api_users_get_top_inside_square")
     */
    public function usersGetTopInsideSquare(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $count = $request->query->get('count', 50);
        $x1 = $request->query->get('x1');
        $x2 = $request->query->get('x2');
        $y1 = $request->query->get('y1');
        $y2 = $request->query->get('y2');
        $maxRank = $request->query->get('maxRank');
        $minRank = $request->query->get('minRank');

        if ($maxRank !== null) {
            $maxRank = (int) $maxRank;
        }

        if ($minRank !== null) {
            $minRank = (int) $minRank;
        }

        $response = [];

        if (is_null($x1) or is_null($x2) or is_null($y1) or is_null($y2) or is_null($count)) {
            return $this->jsonError(403, 'Invalid input params');
        } else {
            $ur = $em->getRepository(User::class);

            foreach ($ur->findByCoordinates($x1, $y1, $x2, $y2, $count, $maxRank, $minRank) as $user) {
                $response[] = $this->serializeUser($user);
            }
        }

        return $this->jsonResponse($response);
    }

    /**
     * Изменение только локали юзера.
     *
     * @Route("/updateLocale", methods={"POST"}, name="api_users_update_locale")
     */
    public function usersUpdateLocale(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->user = $this->getUser();

        if (empty($this->user)) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $input = json_decode($request->getContent(), true);
        $locale = $input['locale'] ?? null;

        // @todo сделать список поддерживаемых локалей.
        $locales = ['en', 'ru', 'pl', 'sk'];

        if (!in_array($locale, $locales)) {
            return $this->jsonError(self::ERROR_NOT_VALID, 'Не поддерживаемая локаль');
        }

        $this->user->setLocale($locale);

        $em->persist($this->user);
        $em->flush();

        return $this->jsonResponse(null);
    }
}
