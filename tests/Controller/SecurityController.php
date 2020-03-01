<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityController extends AbstractController
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
        UserAuthenticator $authenticator
    ): Response {
        /** @var User $user */
        $user = $em->find(User::class, $id);

        if (empty($user)) {
            return new JsonResponse(['user not found'], 404);
        }

        $authenticatedToken = $authenticator->createAuthenticatedToken($user, 'main');
        $authenticationManager->authenticate($authenticatedToken);
        $tokenStorage->setToken($authenticatedToken);

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $authenticatedToken);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        $response = [
            'status' => 'success',
            'message' => 'test login success',
            'user'   => $this->serializeUser($user),
        ];

        return new JsonResponse(['response' => $response]);
    }

    /**
     * @param User $user
     * @param bool $forcePassport
     *
     * @return array
     *
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
            'status' => $user->getStatus(),
            'passport' => $user->getPassportCode(),
            'photo' => $user->getPhoto(),
            'smallPhoto' => $user->getPhoto(),
        ];
    }
}
