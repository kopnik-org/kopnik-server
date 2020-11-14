<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Event\UserEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/api/users")
 */
class ApiUsersForemanController extends AbstractApiController
{
    /**
     * Подать/отменить заявку от имени текущего пользователя на выбор другого пользователя старшиной
     *
     * @Route("/putForemanRequest", methods={"POST"}, name="api_users_put_foreman_request")
     */
    public function putForemanRequest(Request $request, EntityManagerInterface $em, EventDispatcherInterface $dispatcher): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        if ($user->getStatus() === User::STATUS_NEW
            or $user->getStatus() === User::STATUS_PENDING
            or $user->getStatus() === User::STATUS_DECLINE
        ) {
            return $this->jsonError(1000 + 403, 'Нет доступа к выбору старшины');
        }

        $input = json_decode($request->getContent(), true);
        $foreman = $input['id'] ?? null;

        if ($foreman) {
            $foreman = $em->getRepository(User::class)->findOneBy(['id' => $foreman]);

            if (empty($foreman)) {
                return $this->jsonError(1000 + 404, 'Старшина не найден');
            }

            if ($foreman->getKopnikRole() != User::ROLE_KOPNIK and $foreman->getKopnikRole() != User::ROLE_DANILOV_KOPNIK) {
                return $this->jsonError(1000 + 510, 'Старшина не Копник и не Копник по Данилову');
            }

            if ($foreman->getStatus() != User::STATUS_CONFIRMED) {
                return $this->jsonError(1000 + 510, 'Старшина не является заверенным пользователем');
            }
        }

        $user->setForemanRequest($foreman);

        $em->persist($user);
        $em->flush();

        $dispatcher->dispatch($user, UserEvent::FOREMAN_REQUEST);

        return $this->jsonResponse(true);
    }

    /**
     * Получить заявки других пользователей на выбор текущего пользователя своим старшиной.
     *
     * @Route("/getForemanRequests", methods={"GET"}, name="api_users_get_foreman_requests")
     */
    public function getForemanRequests(): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        $response = [];
        foreach ($user->getForemanRequests() as $foremanRequest) {
            $response[] = $this->serializeUser($foremanRequest);
        }

        return $this->jsonResponse($response);
    }

    /**
     * Одобрить заявку другого пользователя на выбор текущего пользователя старшиной.
     *
     * Вызывает старшина, в качестве аргумента приходит ид претендента на подчинённого.
     *
     * @Route("/confirmForemanRequest", methods={"POST"}, name="api_users_confirm_foreman_request")
     */
    public function confirmForemanRequest(Request $request, EntityManagerInterface $em, EventDispatcherInterface $dispatcher): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        $input = json_decode($request->getContent(), true);
        $challenger = $input['id'] ?? null; // Идентификатор пользователя, подавшего заявку

        if ($challenger) {
            $challenger = $em->getRepository(User::class)->find((int) $challenger);

            if (empty($challenger)) {
                return $this->jsonError(1000 + 404, 'User подавший заявку не найден');
            }

            if ($challenger->getForemanRequest() == $user) {
                $dispatcher->dispatch($challenger, UserEvent::FOREMAN_CONFIRM_BEFORE_CHANGE);

                $challenger->setForeman($user);

                $em->flush();

                $dispatcher->dispatch($challenger, UserEvent::FOREMAN_CONFIRM_AFTER_CHANGE);
            } else {
                return $this->jsonError(1000 + 511, 'Неверная заявка на выбор старшины');
            }
        } else {
            return $this->jsonError(1000 + 404, 'User подавший заявку не указан');
        }

        return $this->jsonResponse(true);
    }

    /**
     * Отклонить заявку другого пользователя на выбор текущего пользователя старшиной.
     *
     * @Route("/declineForemanRequest", methods={"POST"}, name="api_users_decline_foreman_request")
     */
    public function declineForemanRequest(Request $request, EntityManagerInterface $em, EventDispatcherInterface $dispatcher): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        $input = json_decode($request->getContent(), true);
        $challenger = $input['id'] ?? null; // Идентификатор пользователя, подавшего заявку

        if ($challenger) {
            $challenger = $em->getRepository(User::class)->findOneBy(['id' => $challenger]);

            if (empty($challenger)) {
                return $this->jsonError(1000 + 404, 'User не найден');
            }

            if ($challenger->getForemanRequest() == $user) {
                $challenger->setForemanRequest(null);

                $em->flush();

                $dispatcher->dispatch($challenger, UserEvent::FOREMAN_DECLINE);
            } else {
                return $this->jsonError(1000 + 511, 'Неверная заявка на выбор старшины');
            }
        } else {
            return $this->jsonError(1000 + 404, 'User не найден');
        }

        return $this->jsonResponse(true);
    }

    /**
     * Отменить выбор старшины. Метод имеет двойное значение в зависимости от того присутствует параметр id или нет.
     *
     * Если парамер присутствует, метод имеет значение: текущий пользователь исключает указанного в id пользователя из подчиненных.
     *
     * Если параметр отсутствует, метод имеет следующеее значение: текущий пользователь выходит из подчиненния своего текущего старшины.
     *
     * @Route("/resetForeman", methods={"POST"}, name="api_users_reset_foreman")
     */
    public function resetForeman(Request $request, EventDispatcherInterface $dispatcher, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        $input = json_decode($request->getContent(), true);
        $subordinate = $input['id'] ?? null; // Идентификатор подчинённого пользователя

        if ($subordinate) {
            $subordinate = $em->getRepository(User::class)->find((int) $subordinate);

            if ($subordinate === null) {
                return $this->jsonError(1000 + 404, 'Указан не существующий юзер для сброса старшины.');
            }

            // Старшина исключает подчинённого
            if ($subordinate->getForeman() == $user) {
                $dispatcher->dispatch($subordinate, UserEvent::SUBORDINATE_RESET);

                $subordinate->setForeman(null);

                $em->flush();
            } else {
                return $this->jsonError(1000 + 512, 'Неверная заявка на выбор старшины');
            }
        } else {
            // Пользователь отказался от старшины
            if ($user->getForeman()) {
                $dispatcher->dispatch($user, UserEvent::FOREMAN_RESET);

                $user->setForeman(null);

                $em->flush();
            }
        }

        return $this->jsonResponse(true);
    }

    /**
     * Получить подчиненных пользователя. Если параметр id===null, метод работает для текущего пользователя.
     *
     * @Route("/getSubordinates", methods={"GET"}, name="api_users_get_subordinates")
     */
    public function getSubordinates(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $this->user = $this->getUser();

        try {
            if ($request->query->has('id') and is_numeric($request->query->get('id'))) {
                $user = $em->getRepository(User::class)->find($request->query->get('id'));
            }
        } catch (ORMException $e) {
            // dummy
        }

        if (empty($user)) {
            $user = $this->getUser();
        }

        $response = [];
        foreach ($user->getSubordinatesUsers() as $subordinatesUser) {
            $response[] = $this->serializeUser($subordinatesUser);
        }

        return $this->jsonResponse($response);
    }

    /**
     * Получить старшину пользователя. Если параметр id===null, метод работает для текущего пльзователя.
     *
     * @Route("/getForeman", methods={"GET"}, name="api_users_get_foreman")
     */
    public function getForeman(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        $user = $em->getRepository(User::class)->find($request->query->get('id'));

        if (empty($user)) {
            $user = $this->getUser();
        }

        $foreman = $user->getForeman();

        if ($foreman) {
            return $this->jsonResponse($this->serializeUser($foreman));
        }

        return $this->jsonResponse(null);
    }

    /**
     * Получить подчиненных пользователя включая подчиненных прямых подчиненных. Если параметр id===null, метод работает для текущего пользователя.
     *
     * @Route("/getAllSubordinates", methods={"GET"}, name="api_users_get_all_subordinates")
     *
     * @todo
     */
    public function getAllSubordinates(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        return $this->jsonResponse(true);
    }

    /**
     * Получить всех старшин пользователя в порядке близости по копному дереву (непосредственный старшина идет первым в списке).
     * Если параметр id===null, метод работает для текущего пльзователя.
     *
     * @Route("/getAllForemans", methods={"GET"}, name="api_users_get_all_foremans")
     *
     * @todo
     */
    public function getAllForemans(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        /** @var User $user */
        $this->user = $user = $this->getUser();

        return $this->jsonResponse(true);
    }
}
