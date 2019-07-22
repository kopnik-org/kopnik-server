<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @param UserRepository $ur
     *
     * @return Response
     *
     * @Route("/", name="homepage")
     */
    public function index(UserRepository $ur): Response
    {
        return $this->render('default/index.html.twig', [
            'users' => $ur->findNear($this->getUser()),
        ]);
    }

    /**
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @return Response|RedirectResponse
     *
     * @Route("/geolocation/", name="geolocation")
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

            return $this->redirectToRoute('geolocation');
        }

        return $this->render('default/geolocation.html.twig', [
            'latitude'  => $user->getLatitude(),
            'longitude' => $user->getLongitude(),
        ]);
    }

    /**
     * @param UserRepository $ur
     *
     * @return Response
     *
     * @Route("/stats/", name="stats")
     */
    public function stats(UserRepository $ur): Response
    {
        return $this->render('default/stats.html.twig', [
            'total' => $ur->countBy([]),
            'confirmed' => $ur->countBy(['is_confirmed' => true]),
            'has_foreman' => $ur->countBy(['foreman' => 'not null']),
            'wo_geo' => $ur->countBy(['latitude' => 'null', 'longitude' => 'null']),
        ]);
    }
}
