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

class UserRolePromoteCommand extends Command
{
    protected static $defaultName = 'app:user:role:promote';

    /** @var SymfonyStyle */
    protected $io;
    protected $em;

    /**
     * UserRolePromoteCommand constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Promotes a user by adding a role')
            ->addArgument('username', InputArgument::REQUIRED, 'The username')
            ->addArgument('role', InputArgument::OPTIONAL, 'The role')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        if (null !== $username) {
            $this->io->text(' > <info>Username</info>: '.$username);
        } else {
//            $username = $this->io->ask('Username');
            $username = $this->io->ask('ID');
            $input->setArgument('username', $username);
        }

        $role = $input->getArgument('role');
        if (null !== $role) {
            $this->io->text(' > <info>Role</info>: '.$role);
        } else {
            $role = $this->io->ask('Role');
            $input->setArgument('role', $role);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');

        //$user = $this->em->getRepository(User::class)->findOneByUsername($username);
        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $username]);

        if (empty($user)) {
            $this->io->warning('User not found');

            return 0;
        }

        if ($user->hasRole($role)) {
            $this->io->warning(sprintf('User "%s" did already have "%s" role.', $username, $role));

            return 0;
        }

        $user->setRole((int) $role);

        $this->em->flush();

        $this->io->success(sprintf('User "%s" has been promoted as a super administrator. This change will not apply until the user logs out and back in again.', $username));

        return 0;
    }
}
