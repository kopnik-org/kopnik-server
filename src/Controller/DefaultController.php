<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index($slug = null, EntityManagerInterface $em, KernelInterface $kernel): Response
    {
        $templatePath = $kernel->getProjectDir() . '/public/index.html';

        if (file_exists($templatePath)) {
            return new Response(file_get_contents($templatePath));
        }

        $ur = $em->getRepository(User::class);

        return $this->render('default/index.html.twig', [
            'users' => $ur->findNear($this->getUser()),
        ]);
    }

    /**
     * Route("/backend/stats/", name="stats")
     */
    public function stats(EntityManagerInterface $em): Response
    {
        $ur = $em->getRepository(User::class);

        return $this->render('default/stats.html.twig', [
            'total' => $ur->countBy([]),
            'confirmed' => $ur->countBy(['status' => User::STATUS_CONFIRMED]),
            'witnesses' => $ur->countBy(['is_witness' => true]),
            'has_foreman' => $ur->countBy(['foreman' => 'not null']),
        ]);
    }

    /**
     * Route("/backend/assurance/", name="assurance")
     */
    public function assurance(EntityManagerInterface $em): Response
    {
        if ($this->getUser()->getStatus() == User::STATUS_CONFIRMED) {
            return $this->redirectToRoute('homepage');
        }

        $ur = $em->getRepository(User::class);

        return $this->render('default/assurance.html.twig', [
            'witness' => $ur->findOneBy(['is_witness' => true], ['created_at' => 'ASC']),
        ]);
    }

    /**
     * Route("/backend/admin/", name="admin")
     */
    public function admin(Request $request, EntityManagerInterface $em, $vkCallbackApiAccessToken): Response
    {
        if (!$this->getUser()->isWitness()) {
            return $this->redirectToRoute('homepage');
        }

        $action = $request->query->get('action');
        $ur = $em->getRepository(User::class);

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

                try {
                    $vk = new VKApiClient();

                    $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                        'user_id' => $user->getVkIdentifier(),
                        'message' => "Заверение пройдено успешно!",
                        'random_id' => random_int(100, 999999999),
                    ]);
                } catch (VKApiFloodException $e) {
                    $this->addFlash('error', $e->getMessage());

                    return $this->redirectToRoute('admin');
                } catch (VKApiException $e) {
                    $this->addFlash('error', $e->getMessage());

                    return $this->redirectToRoute('admin');
                }

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

            try {
                $vk = new VKApiClient();

                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $user->getVkIdentifier(),
                    'message' => "Заявка на заверение отклонена. Пожалуйста, исправьте ваши анкетные данные и повторите запрос.",
                    'random_id' => random_int(100, 999999999),
                ]);
            } catch (VKApiFloodException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('admin');
            } catch (VKApiException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('admin');
            }

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
