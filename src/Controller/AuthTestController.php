<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function anyMethod(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok'
        ]);
    }
}
