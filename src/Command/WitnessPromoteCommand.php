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

class WitnessPromoteCommand extends Command
{
    protected static $defaultName = 'app:witness:promote';

    private $io;
    private $em;

    protected function configure()
    {
        $this
            ->setDescription('Назначить пользователя заверителем')
            ->addArgument('vk_id', InputArgument::REQUIRED, 'VK ID - номер страницы')
            //->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    /**
     * MakeWitnessCommand constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
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
            $this->io->note($user->getFirstName().' '.$user->getLastName().' - уже является заверителем');

            return;
        }

        $user
            ->setIsWitness(true)
            ->setStatus(User::STATUS_CONFIRMED)
        ;
        $this->em->flush();

        $this->io->success($user->getFirstName().' '.$user->getLastName().' - назначен заверителем');
    }
}
