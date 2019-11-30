<?php

declare(strict_types=1);

namespace App\Controller;

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
            $users[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstName(),
                'patronymic' => $user->getPatronymic(),
                'lastname' => $user->getLastName(),
                'foreman' => $user->getForeman(),
                'witness' => $user->getWitness(),
                'latitude' => $user->getLatitude(),
                'longtitude' => $user->getLongitude(),
//                '' => $user->get(),
            ];
        }

        $data = [
            'users' => $users,
        ];

        return new JsonResponse($data);
    }
}
