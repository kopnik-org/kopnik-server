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

class WitnessListCommand extends Command
{
    protected static $defaultName = 'app:witness:list';

    /** @var SymfonyStyle */
    private $io;
    private $em;

    protected function configure()
    {
        $this
            ->setDescription('Список всех заверителей')
            //->addArgument('vk_id', InputArgument::REQUIRED, 'VK ID - номер страницы')
            //->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    /**
     * WitnessListCommand constructor.
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
        /** @var User[] $users */
        $users = $this->em->getRepository(User::class)->findBy(['is_witness' => 1]);

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getId(),
                $user->__toString(),
                $user->getVkIdentifier()
            ];
        }

        $this->io->table(['id', 'ФИО', 'VK ID'], $rows);
    }
}
