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
     * @Route("/", name="homepage")
     */
    public function index(UserRepository $ur): Response
    {
        return $this->render('default/index.html.twig', [
            'users' => $ur->findNear($this->getUser()),
        ]);
    }

    /**
     * @Route("/stats/", name="stats")
     */
    public function stats(UserRepository $ur): Response
    {
        return $this->render('default/stats.html.twig', [
            'total' => $ur->countBy([]),
            'confirmed' => $ur->countBy(['status' => User::STATUS_CONFIRMED]),
            'witnesses' => $ur->countBy(['is_witness' => true]),
            'has_foreman' => $ur->countBy(['foreman' => 'not null']),
        ]);
    }

    /**
     * @Route("/assurance/", name="assurance")
     */
    public function assurance(UserRepository $ur): Response
    {
        if ($this->getUser()->getStatus() == User::STATUS_CONFIRMED) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('default/assurance.html.twig', [
            'witnesses' => $ur->findBy(['is_witness' => true]),
        ]);
    }

    /**
     * @Route("/admin/", name="admin")
     */
    public function admin(Request $request, UserRepository $ur, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()->isWitness()) {
            return $this->redirectToRoute('homepage');
        }

        $action = $request->query->get('action');


        if ($action) {
            $user = $ur->find($request->query->get('user', 0));
            if (empty($user)) {
                $this->addFlash('error', 'Пользователь не найден');

                return $this->redirectToRoute('admin');
            }
        }

        if ($action == 'confirm') {
            if ($user->getStatus() == User::STATUS_PENDING) {
                $user
                    ->setStatus(User::STATUS_CONFIRMED)
                    ->setConfirmedAt(new \DateTime())
                    ->setWitness($this->getUser())
                ;
                $em->flush();

                $this->addFlash('success', "Пользователь <b>{$user->__toString()}</b> заверен!");
            } else {
                $this->addFlash('error', "У пользователя <b>{$user->__toString()}</b> не статус 'в ожидании'. ");
            }

            return $this->redirectToRoute('admin');
        } elseif ($action == 'decline') {
            $user
                ->setStatus(User::STATUS_DECLINE)
                ->setConfirmedAt(new \DateTime())
                ->setWitness($this->getUser())
            ;
            $em->flush();

            $this->addFlash('notice', "Пользователь <b>{$user->__toString()}</b> отклонён!");

            return $this->redirectToRoute('admin');
        }

        $status = $request->query->get('status', User::STATUS_PENDING);

        return $this->render('default/admin.html.twig', [
            'status' => $status,
            'users'  => $ur->findBy(['status' => $status, 'is_allow_messages_from_community' => true], ['created_at' => 'DESC']),
        ]);
    }
}
