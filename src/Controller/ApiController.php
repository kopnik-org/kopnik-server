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
use Symfony\Component\Routing\Annotation\Route;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /** @var User */
    protected $user;

    /**
     * @Route("/users/update", methods={"POST"}, name="api_users_update")
     */
    public function usersUpdate(Request $request, EntityManagerInterface $em, $vkCallbackApiAccessToken): JsonResponse
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

        $response = [];


        $input = json_decode($request->getContent(), true);
        $data = [
            'firstname'     => $input['firstName'],
            'patronymic'    => $input['patronymic'],
            'lastname'      => $input['lastName'],
            'birth_year'    => $input['birthyear'],
            'passport_code' => $input['passport'],
            'latitude'      => $input['location'][0],
            'longitude'     => $input['location'][1],
//            'photo'         => $input['photo'],
//            'smallPhoto'    => $input['smallPhoto'],
            'update'        => ''
        ];
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
        $form = $this->createForm(UserFormType::class, $this->user, ['csrf_protection' => false]);
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
            $this->getUser()->setStatus(User::STATUS_PENDING);

            try {
                $vk = new VKApiClient();
                /** @var User $user */
                $user = $this->getUser();
                $invite_chat_link = $user->getAssuranceChatInviteLink();

                if (empty($user->getWitness())) {
                    $user->setWitness($witness); // @todo костыль...
                }

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
            }

            $em->persist($this->user);
            $em->flush();

            $response[] = $this->serializeUser($witness);
        } else {
            $errors = [];
            foreach ($form->getErrors() as $error) {
                $errors[] = [
                    'field' => $error->getOrigin()->getName(),
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
