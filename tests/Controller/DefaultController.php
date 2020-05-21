<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\AbstractApiController;
use App\Entity\User;
use App\Entity\UserOauth;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractApiController
{
    /**
     * @Route("/api/test/setupDB", name="test_setup_db")
     */
    public function setupDB(KernelInterface $kernel): JsonResponse
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
            '--full-database' => true,
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ]);
        $application->run($input, $output);

        $input = new ArrayInput([
            'command' => 'hautelook:fixtures:load',
            '-q' => true,
        ]);
        $application->run($input, $output);

        return $this->json(['output' => $output->fetch()]);
    }

    /**
     * @Route("/api/test/createUser", methods={"POST"}, name="test_create_user")
     */
    public function createUser(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $input = json_decode($request->getContent(), true);

        $data = [
            'first_name'    => $input['firstName'] ?? '',
            'last_name'     => $input['lastName'] ?? null,
            'is_witness'    => $input['isWitness'] ?? false,
            'witness_id'    => $input['witness_id'] ?? null,
            'patronymic'    => $input['patronymic'] ?? '',
            'birth_year'    => $input['birthyear'] ?? null,
            'passport_code' => $input['passport'] ?? null,
            'latitude'      => $input['location']['lat'] ?? null,
            'longitude'     => $input['location']['lng'] ?? null,
            'locale'        => $input['locale'] ?? null,
            'role'          => $input['role'] ? (int) $input['role'] : User::ROLE_STRANGER,
            // oAuth
            'identifier'    => $input['identifier'],
            'email'         => $input['email'],
            'access_token'  => $input['access_token'],
            // only test
            'photo'         => $input['photo'] ?? null,
            'smallPhoto'    => $input['smallPhoto'] ?? null,
        ];

        try {
            $userOauth = $em->getRepository(UserOauth::class)->findOneBy([
                'email' => $data['email'],
                'identifier' => $data['identifier'],
                'access_token' => $data['access_token'],
                'provider' => 'vkontakte',
            ]);
        } catch (DriverException $e) {
            return $this->jsonError($e->getCode(), $e->getMessage());
        }

        if ($userOauth) {
            return $this->jsonError(self::ERROR_NOT_VALID, 'Такой юзер уже зареган');
        }

        $user = new User();
        $user
            ->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setPatronymic($data['patronymic'])
            ->setPassportCode($data['passport_code'])
            ->setBirthYear($data['birth_year'])
            ->setLatitude($data['latitude'])
            ->setLongitude($data['longitude'])
            ->setLocale($data['locale'])
            ->setIsWitness((bool) $data['is_witness'])
            ->setRole($data['role'])
            ->setPhoto($data['photo'])
            ->setSmallPhoto($data['smallPhoto'])
        ;

        if ($data['witness_id']) {
            $witness = $em->getRepository(User::class)->find((int) $data['witness_id']);

            if (!$witness) {
                return $this->jsonError(self::ERROR_NOT_VALID, 'Указан не существующий witness_id');
            }

            $user->setWitness($witness);
        }

        $userOauth = new UserOauth();
        $userOauth
            ->setEmail($data['email'])
            ->setAccessToken($data['access_token'])
            ->setIdentifier($data['identifier'])
            ->setProvider('vkontakte')
            ->setUser($user)
        ;

        try {
            $em->persist($userOauth);
            $em->persist($user);
            $em->flush();
        } catch (DriverException $e) {
            return $this->jsonError($e->getCode(), $e->getMessage());
        }

        return $this->json($user->getId());
    }
}
