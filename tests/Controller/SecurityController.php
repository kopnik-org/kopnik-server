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

        /*
        $response = [
            'status' => 'success',
            'message' => 'test login success',
            'user'   => $this->serializeUser($user),
        ];
        */

        return $this->jsonResponse('ok');
    }

    /**
     * @todo вынести в сервис
     *
    protected function serializeUser(User $user, bool $forcePassport = false): array
    {
        $location = new \stdClass();
        $location->lat = $user->getLatitude();
        $location->lng = $user->getLongitude();

        $isAllowTenChatInviteLink = false;

        if ($this->user->getId() === $user->getId()
            or (
                $this->user->getForeman() and $this->user->getForeman()->getId() === $user->getId()
            )
        ) {
            $isAllowTenChatInviteLink = true;
        }

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
            'role' => $user->getKopnikRole(),
            'isWitness' => $user->isWitness(),
            'status' => $user->getStatus(),
            'photo' => $user->getPhoto(),
            'smallPhoto' => $user->getPhoto(),

//            'passport' => $user->getPassportCode(),
//            'foremanRequest_id' => $user->getForemanRequest() ? $user->getForemanRequest()->getId() : null,
//            'tenChatInviteLink' => $user->getTenChatInviteLink(), // там где я старшина
//            'witnessChatInviteLink' => $user->getAssuranceChatInviteLink(),

            'passport' => ($this->user->getId() === $user->getId() or $forcePassport) ? $user->getPassportCode() : null,
            'foremanRequest_id' => ($this->user->getId() === $user->getId() and $user->getForemanRequest()) ? $user->getForemanRequest()->getId() : null,
            'tenChatInviteLink' => $isAllowTenChatInviteLink ? $user->getTenChatInviteLink() : null, // там где я старшина
            'witnessChatInviteLink' => ($this->user->getId() === $user->getId()) ? $user->getAssuranceChatInviteLink() : null,
        ];
    }
     */
}
