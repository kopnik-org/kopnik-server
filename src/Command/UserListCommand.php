<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserListCommand extends Command
{
    protected static $defaultName = 'app:user:list';

    /** @var SymfonyStyle */
    private $io;
    private $em;

    protected function configure()
    {
        $this
            ->setDescription('Список всех пользователей')
        ;
    }

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var User[] $users */
        $users = $this->em->getRepository(User::class)->findBy([], ['id' => 'DESC', 'created_at' => 'DESC']);

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getId(),
                $user->__toString(),
                $user->getVkIdentifier(),
                $user->getPassportCode(),
                $user->getKopnikRole() . ': ' . $user->getKopnikRoleAsText(),
//                $user->getOauthByProvider('vkontakte')->getAccessToken(),
                $user->getStatusAsText(),
                $user->getForeman(),
                $user->getWitness(),
                $user->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }

        //$this->io->table(['id', 'ФИО', 'VK ID', 'VK Access Token', 'Статус', 'Дата регистрации'], $rows);
        $this->io->table(['id', 'ФИО', 'VK ID', 'Code', 'Kopnik Role', 'Статус', 'Старшина', 'Заверитель', 'Дата регистрации'], $rows);

        return 0;
    }
}
