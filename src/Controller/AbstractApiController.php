<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AbstractApiController extends AbstractController
{
    /** @var User */
    // для сериалайзера
    protected $user;

    /**
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
            'rank' => $user->getRank(),
            'role' => $user->getRole(),
            'status' => $user->getStatus(),
            'passport' => ($this->user->getId() == $user->getId() or $forcePassport) ? $user->getPassportCode() : null,
            'photo' => $user->getPhoto(),
            'smallPhoto' => $user->getPhoto(),
        ];
    }

    public function json($response): JsonResponse
    {
        return new JsonResponse(['response' => $response]);
    }

    public function jsonError($code, $msg, ?Request $request = null): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'error_code' => $code,
                'error_msg'  => $msg,
                //'request_params' => '@todo ',
            ]
        ]);
    }

    public function jsonErrorWithValidation($code, $msg, $validation_errors, ?Request $request): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'error_code' => $code,
                'error_msg'  => $msg,
                'validation_errors' => $validation_errors,
                //'request_params' => '@todo ',
            ]
        ]);
    }
}
