<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
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
     * @Route("/test/login/{id}", name="test_security_login")
     */
    public function login(
        $id,
        Request $request,
        EntityManagerInterface $em,
        AuthenticationManagerInterface $authenticationManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        /** @var User $user */
        $user = $em->find(User::class, $id);

        if (empty($user)) {
            return new JsonResponse(['user not found'], 404);
        }

        $unauthenticatedToken = new OAuthToken($user->getOauthByProvider('vkontakte')->getAccessToken());
//        $authenticatedToken = $authenticationManager->authenticate($unauthenticatedToken);
        $tokenStorage->setToken($unauthenticatedToken);

        $request->getSession()->set('_security_main', serialize($unauthenticatedToken));

        $oAuthToken = $request->getSession()->get('_security_main');

        var_dump($oAuthToken);

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $unauthenticatedToken);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        return new Response('login: '.$user);
    }
}
