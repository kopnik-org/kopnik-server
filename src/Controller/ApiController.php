<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /**
     * @Route("/users/witness_request", methods={"POST"}, name="api_users_witness_request")
     */
    public function usersWitnessRequest(Request $request): JsonResponse
    {
        return new JsonResponse([
            'get' => $request->query->all(),
            'post' => $request->request->all(),
        ]);
    }

    /**
     * @Route("/users/get", methods={"GET"}, name="api_users_get")
     */
    public function usersGet(Request $request, UserRepository $ur): JsonResponse
    {
        $ids = $request->query->get('ids');

        if (empty($ids)) {
            return new JsonResponse([
                'error' => [
                    'error_code' => 1,
                    'error_msg'  => 'Invalid user ids',
                    'request_params' => '@todo ',
                ]
            ]);
        }

        $ids = explode(',', $ids);

        $response = [];

        foreach ($ids as $id) {
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

        return new JsonResponse(['response' => $response]);
    }
    
    /**
     * @Route("/user/list", name="api_user_list")
     */
    public function usersList(Request $request, UserRepository $ur): JsonResponse
    {
        $users = [];

        foreach ($ur->findNear($this->getUser()) as $user) {
            $users[$user->getId()] = $this->serializeUser($user);
        }

        $data = [
            'status' => 'success',
            'users' => $users,
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/user/{id}", name="api_user")
     */
    public function user($id, UserRepository $ur): JsonResponse
    {
        $user = $ur->find($id);

        if ($user) {
            $data = [
                'status' => 'success',
                'user' => $this->serializeUser($user),
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => 'User not found',
            ];
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
            'firstname' => $user->getFirstName(),
            'patronymic' => $user->getPatronymic(),
            'lastname' => $user->getLastName(),
            'foreman_id' => $user->getForeman() ? $user->getForeman()->getId() : null,
            'witness_id' => $user->getWitness() ? $user->getWitness()->getId() : null,
            'birthyear' => $user->getBirthYear(),
            'location' => [$user->getLatitude(), $user->getLongitude()],
            // '' => $user->get(),
        ];
    }
}
