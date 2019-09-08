<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @return RedirectResponse|Response
     *
     * @Route("/", name="profile")
     */
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserFormType::class, $this->getUser());

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('update')->isClicked() and $form->isValid()) {
                $em->persist($this->getUser());
                $em->flush();

                $this->addFlash('success', 'Основные данные обновлены');

                return $this->redirectToRoute('profile');
            }
        }

        return $this->render('profile/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @return Response|RedirectResponse
     *
     * @Route("/geolocation/", name="profile_geolocation")
     */
    public function geolocation(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user
                ->setLatitude((float) $request->request->get('latitude'))
                ->setLongitude((float) $request->request->get('longitude'))
            ;

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Координаты сохранены.');

            return $this->redirectToRoute('profile_geolocation');
        }

        return $this->render('profile/geolocation.html.twig', [
            'latitude'  => $user->getLatitude(),
            'longitude' => $user->getLongitude(),
        ]);
    }
}
