<?php

declare(strict_types=1);

namespace App\Test\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function login(AuthenticationUtils $helper): Response
    {
        return $this->render('security/login.html.twig', [

        ]);
    }
}
