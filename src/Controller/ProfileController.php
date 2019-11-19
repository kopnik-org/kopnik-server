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
use VK\Client\VKApiClient;
use VK\Exceptions\Api\VKApiFloodException;
use VK\Exceptions\VKApiException;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/", name="profile")
     */
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserFormType::class, $this->getUser());

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('update')->isClicked() and $form->isValid()) {
                if ($this->getUser()->getStatus() == User::STATUS_DECLINE) {
                    $this->getUser()->setStatus(User::STATUS_PENDING);
                }

                $em->persist($this->getUser());
                $em->flush();

                $this->addFlash('success', 'Профиль обновлён');

                return $this->redirectToRoute('profile');
            }
        }

        return $this->render('profile/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/allow_messages_from_community/", name="profile_allow_messages_from_community")
     */
    public function allowMessagesFromCommunity(Request $request, EntityManagerInterface $em, $vkCommunityId, $vkCallbackApiAccessToken): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isAllowMessagesFromCommunity()) {
            return $this->redirectToRoute('homepage');
        }

        try {
            $vk = new VKApiClient();
            $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                'user_id' => $user->getVkIdentifier(),
                // 'domain' => 'some_user_name',
                'message' => 'Добро пожаловать в kopnik.org!',
                'random_id' => random_int(100, 999999999),
            ]);

            $user
                ->setIsAllowMessagesFromCommunity(true)
                ->setStatus(User::STATUS_PENDING)
            ;
            $em->flush();

            return $this->redirectToRoute('homepage');
        } catch (VKApiFloodException $e) {
            // @todo
        } catch (VKApiException $e) {
            if ($e->getErrorCode() == 901 and $user->isAllowMessagesFromCommunity()) {
                $user->setIsAllowMessagesFromCommunity(false);
                $em->flush();

                return $this->redirectToRoute('profile_allow_messages_from_community');
            }
        }

        return $this->render('profile/allow_messages_from_community.html.twig', [
            'vk_community_id' => $vkCommunityId,
        ]);
    }
}
