<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/users")
 */
class ApiUsersForemanController extends AbstractApiController
{
    /**
     * Подать/отменить заявку от имени текущего пользователя на выбор другого пользователя старшиной
     *
     * @Route("/putForemanRequest", methods={"POST"}, name="api_users_put_foreman_request")
     *
     * @todo
     */
    public function putForemanRequest(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $this->user = $user = $this->getUser();


        return $this->json(true);
    }

    /**
     * Получить заявки других пользователей на выбор текущего пользователя своим старшиной.
     *
     * @Route("/getForemanRequests", methods={"GET"}, name="api_users_get_foreman_requests")
     *
     * @todo
     */
    public function getForemanRequests(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $this->user = $user = $this->getUser();

        return $this->json(true);
    }

    /**
     * Одобрить заявку другого пользователя на выбор текущего пользователя старшиной.
     *
     * @Route("/confirmForemanRequest", methods={"POST"}, name="api_users_confirm_foreman_request")
     *
     * @todo
     */
    public function confirmForemanRequest(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $this->user = $user = $this->getUser();

        return $this->json(true);
    }

    /**
     * Отклонить заявку другого пользователя на выбор текущего пользователя старшиной.
     *
     * @Route("/declineForemanRequest", methods={"POST"}, name="api_users_decline_foreman_request")
     *
     * @todo
     */
    public function declineForemanRequest(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $this->user = $user = $this->getUser();

        return $this->json(true);
    }

    /**
     * Отменить выбор старшины. Метод имеет двойное значение в зависимости от того присутствует параметр id или нет.
     *
     * Если парамер присутствует, метод имеет значение: текущий пользователь исключает указанного в id пользователя из подчиненных.
     *
     * Если параметр отсутствует, метод имеет следующеее значение: текущий пользователь выходит из подчиненния своего текущего старшины.
     *
     * @Route("/resetForeman", methods={"POST"}, name="api_users_reset_foreman")
     *
     * @todo
     */
    public function resetForeman(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $this->user = $user = $this->getUser();

        return $this->json(true);
    }

    /**
     * Получить подчиненных пользователя. Если параметр id===null, метод работает для текущего пользователя.
     *
     * @Route("/getSubordinates", methods={"GET"}, name="api_users_get_subordinates")
     *
     * @todo
     */
    public function getSubordinates(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (empty($this->getUser())) {
            return $this->jsonError(self::ERROR_UNAUTHORIZED, 'No authentication');
        }

        $this->user = $user = $this->getUser();

        return $this->json(true);
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

        $this->user = $user = $this->getUser();

        return $this->json(true);
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

        $this->user = $this->getUser();

        $user = $em->getRepository(User::class)->find($request->query->get('id'));

        if (empty($user)) {
            $user = $this->getUser();
        }

        $foreman = $user->getForeman();

        if ($foreman) {
            return $this->json($this->serializeUser($foreman));
        }

        return $this->json(null);
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

        $this->user = $user = $this->getUser();

        return $this->json(true);
    }
}
