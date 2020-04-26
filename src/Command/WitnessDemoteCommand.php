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

class WitnessDemoteCommand extends Command
{
    protected static $defaultName = 'app:witness:demote';

    private $io;
    private $em;

    protected function configure()
    {
        $this
            ->setDescription('Лишить пользователя статуса заверителя')
            ->addArgument('vk_id', InputArgument::REQUIRED, 'VK ID - номер страницы')
            //->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
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
        $vk_id = $input->getArgument('vk_id');

        $userOauth = $this->em->getRepository('App:UserOauth')->findOneBy(['identifier' => (int) $vk_id]);

        if (empty($userOauth)) {
            $this->io->error('Пользователь с VK ID: '.(int) $vk_id.' не зарегистрирован в системе');

            return;
        }

        /** @var User $user */
        $user = $userOauth->getUser();

        if ($user->isWitness()) {
            $user->setIsWitness(false);
            $this->em->flush();

            $this->io->success($user->getFirstName().' '.$user->getLastName().' - лишен статуса заверитель');

            return;
        }

        $this->io->note($user->getFirstName().' '.$user->getLastName().' - не является заверителем');

        return 0;
    }
}
