<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserOauth;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /** @var User */
    protected $user;

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
