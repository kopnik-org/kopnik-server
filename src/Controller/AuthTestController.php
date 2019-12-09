<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/auth-test")
 */
class AuthTestController extends AbstractController
{
    /**
     * @Route("/any-method", name="authtest_any_method")
     */
    public function anyMethod(Request $request): JsonResponse
    {
        //$user = $this->getUser();

        return new JsonResponse([
            'get_value_from_session' => $request->getSession()->get('val'),
        ]);
    }

    /**
     * @Route("/login", name="authtest_login")
     */
    public function login(Request $request): JsonResponse
    {
        $request->getSession()->set('val', $request->query->get('val'));

        return new JsonResponse([
            'set_value_from_session' => $request->query->get('val'),
        ]);
    }
}
