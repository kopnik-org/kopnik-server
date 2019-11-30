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
     * @Route("/users/", name="api_users")
     */
    public function users(Request $request, UserRepository $ur): JsonResponse
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
            'foreman' => $user->getForeman() ? $user->getForeman()->getId() : null,
            'witness' => $user->getWitness() ? $user->getWitness()->getId() : null,
            'latitude' => $user->getLatitude(),
            'longtitude' => $user->getLongitude(),
            // '' => $user->get(),
        ];
    }
}
