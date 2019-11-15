<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VkController extends AbstractController
{
    /**
     * @param UserRepository $ur
     *
     * @return Response
     *
     * @Route("/vk_callback", name="vk_callback")
     */
    public function callback(Request $request): Response
    {
        return new Response('9017782e');
    }
}
