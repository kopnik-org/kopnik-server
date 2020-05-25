<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\AbstractApiController;
use App\Entity\User;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SecurityController extends AbstractApiController
{
    /**
     * @Route("/api/test/login/{id}", name="test_security_login")
     */
    public function login(
        $id,
        Request $request,
        EntityManagerInterface $em,
        AuthenticationManagerInterface $authenticationManager,
        TokenStorageInterface $tokenStorage,
        UserAuthenticator $authenticator,
        EventDispatcherInterface $dispatcher
    ): JsonResponse {
        /** @var User $user */
        $user = $em->find(User::class, $id);

        if ($user === null) {
            return $this->jsonError(404, 'user not found');
        }

        $authenticatedToken = $authenticator->createAuthenticatedToken($user, 'main');
        $authenticationManager->authenticate($authenticatedToken);
        $tokenStorage->setToken($authenticatedToken);

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $authenticatedToken);
        $dispatcher->dispatch($event, 'security.interactive_login');

        $response = [
            'status' => 'success',
            'message' => 'test login success',
            'user'   => $this->serializeUser($user),
        ];

        return $this->jsonResponse($response);
    }

    /**
     * @todo вынести в сервис
     */
    protected function serializeUser(User $user, bool $forcePassport = false): array
    {
        $location = new \stdClass();
        $location->lat = $user->getLatitude();
        $location->lng = $user->getLongitude();

        $foremanRequestId = $user->getForemanRequest() ? $user->getForemanRequest()->getId() : null;

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
            'isWitness' => $user->isWitness(),
            'status' => $user->getStatus(),
            'passport' => $user->getPassportCode(),
            'photo' => $user->getPhoto(),
            'smallPhoto' => $user->getPhoto(),
            'foremanRequest_id' => $foremanRequestId,
        ];
    }
}
