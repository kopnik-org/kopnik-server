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
 * Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * Route("/", name="profile")
     */
    public function profile(Request $request, EntityManagerInterface $em, $vkCallbackApiAccessToken): Response
    {
        $form = $this->createForm(UserFormType::class, $this->getUser());

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('update')->isClicked() and $form->isValid()) {
                if ($this->getUser()->getStatus() == User::STATUS_DECLINE) {
                    $this->getUser()->setStatus(User::STATUS_PENDING);

                    try {
                        $vk = new VKApiClient();
                        $user = $this->getUser();
                        $invite_chat_link = $user->getAssuranceChatInviteLink();

                        $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                            'user_id' => $user->getVkIdentifier(),
                            // 'domain' => 'some_user_name',
                            'message' => "Повторная заявка на заверение в kopnik-org! Перейдите в чат по ссылке $invite_chat_link и договоритеcь о заверении аккаунта.",
                            'random_id' => random_int(100, 999999999),
                        ]);

                        $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                            'user_id' => $user->getWitness()->getVkIdentifier(),
                            // 'domain' => 'some_user_name',
                            'message' => "Повторная заявка на заверение нового пользователя {$user} ссылка на чат $invite_chat_link",
                            'random_id' => random_int(100, 999999999),
                        ]);
                    } catch (VKApiFloodException $e) {
                        $this->addFlash('error', $e->getMessage());

                        return $this->redirectToRoute('profile');
                    } catch (VKApiException $e) {
                        $this->addFlash('error', $e->getMessage());

                        return $this->redirectToRoute('profile');
                    }
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
     * Route("/allow_messages_from_community/", name="profile_allow_messages_from_community")
     */
    public function allowMessagesFromCommunity(Request $request, EntityManagerInterface $em, $vkCommunityId, $vkCallbackApiAccessToken): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isAllowMessagesFromCommunity()) {
            return $this->redirectToRoute('homepage');
        }

        // @todo Пока так находит первого и единственного заверителя
        $witness = $em->getRepository(User::class)->findOneBy(['is_witness' => true], ['created_at' => 'ASC']);

        if (empty($witness)) {
            return $this->render('profile/_witness_not_found.html.twig');
        }

        try {
            $vk = new VKApiClient();

            /**
             * 1) Создать групповой чат с заверителем и новобранцем
             * 2) Получить ссылку приглашения в чат
             * 3) Написать ссылку-приглашение в чат новобранцу
             * 4) Написать ссылку-приглашение в чат заверителю
             * 5) Сохранить ссылку-приглашение в профиле юзера
             * 6) Отобразить ссылку-приглашение на странице assurance
             */

            if ($user->isWitness()) {
                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $user->getVkIdentifier(),
                    // 'domain' => 'some_user_name',
                    'message' => "Добро пожаловать в kopnik-org! Вы уже являетесь заверителем.",
                    'random_id' => random_int(100, 999999999),
                ]);

                $user->setIsAllowMessagesFromCommunity(true);
            } else {
                // 1) Создать групповой чат с заверителем и новобранцем
                $chat_id = $vk->messages()->createChat($vkCallbackApiAccessToken, [
                    'user_ids' => "{$user->getVkIdentifier()},{$witness->getVkIdentifier()}",
                    'title' => "{$user} - Заверение пользователя в Копнике",
                    'group_id' => $vkCommunityId,
                    //'v' => '5.103'
                ]);

                // 2) Получить ссылку приглашения в чат
                $invite_chat_link = $vk->messages()->getInviteLink($vkCallbackApiAccessToken, [
                    'peer_id' => 2000000000 + $chat_id,
                    'group_id' => $vkCommunityId,
                    'reset' => 0,
                ])['link'];

                // 3) Написать ссылку-приглашение в чат новобранцу
                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $user->getVkIdentifier(),
                    // 'domain' => 'some_user_name',
                    'message' => "Добро пожаловать в kopnik-org! Для заверения, пожалуйста, перейдите в чат по ссылке $invite_chat_link и договоритеcь о заверении аккаунта.",
                    'random_id' => random_int(100, 999999999),
                ]);

                // 4) Написать ссылку-приглашение в чат заверителю
                $result = $vk->messages()->send($vkCallbackApiAccessToken, [
                    'user_id' => $witness->getVkIdentifier(),
                    // 'domain' => 'some_user_name',
                    'message' => "Зарегистрировался новый пользователь {$user} ссылка на чат $invite_chat_link",
                    'random_id' => random_int(100, 999999999),
                ]);

                $user
                    ->setAssuranceChatInviteLink($invite_chat_link) // 5) Сохранить ссылку-приглашение в профиле юзера
                    ->setIsAllowMessagesFromCommunity(true)
                    ->setStatus(User::STATUS_PENDING)
                ;
            }
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
