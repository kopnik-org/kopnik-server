<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TechCommand extends Command
{
    protected static $defaultName = 'app:tech';

    private $io;
    private $em;

    protected function configure()
    {
        $this
            ->setDescription('Для технических целей')
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
        $ur = $this->em->getRepository(User::class);

        $user1 = $ur->findByEmail('sstroman@hotmail.com'); //  Трофим Калашников
        $user2 = $ur->findByEmail('emoen@yahoo.com'); //  Павел Сысоев
        $user3 = $ur->findByEmail('jayda.stamm@johnson.com'); //  Иммануил Некрасов
        $user7 = $ur->findByEmail('maximillian51@terry.net'); //  Рама Карпов
        $user13 = $ur->findByEmail('obins@ledner.com'); //  Вячеслав Бобров
        $user15 = $ur->findByEmail('xeffertz@gislason.biz'); //  Иван Копылов

        /*
        if ($user1) {
            dump($user1->getId() . ' - ' . $user1);
        }

        if ($user2) {
            dump($user2->getId() . ' - ' . $user2);
        }

        if ($user3) {
            dump($user3->getId() . ' - ' . $user3);
        }
*/


//        $user7->setForeman($user1);
//        $this->em->flush();
//        $user7->setForeman(null);
//        $this->em->flush();

//        $user15->setForeman($user1);
//        $this->em->flush();

//        $user3->setForeman($user2);
//        $this->em->flush();

//        $user13->setForeman($user7);
//        $this->em->flush();

        return 0;
    }
}
