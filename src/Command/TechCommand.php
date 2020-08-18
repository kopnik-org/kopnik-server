<?php

declare(strict_types=1);

namespace App\Command;

use App\Contracts\MessengerInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class TechCommand extends Command
{
    protected static $defaultName = 'app:tech';

    private $io;
    private $em;
    private $vk;
    private $kernel;

    protected function configure()
    {
        $this
            ->setDescription('Для технических целей')
        ;
    }

    public function __construct(
        EntityManagerInterface $em,
        MessengerInterface $vk,
        KernelInterface $kernel
    ) {
        parent::__construct();

        $this->em = $em;
        $this->vk = $vk;
        $this->kernel = $kernel;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ur = $this->em->getRepository(User::class);

        /*
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

    public function generateVkAvatarsCollection()
    {
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            $userIds = (string) rand(1, 606214768);

            for ($i2 = 1; $i2 <= 1000; $i2++) {
                $userIds .= ',' . rand(1, 606214768);

            }

            $result = $this->vk->getUser($userIds);

            foreach ($result as $item) {
                $photo = $item['photo_200'] ?? null;
                $sex = $item['sex'] ?? null;
                $bdate = $item['bdate'] ?? null;

                try {
                    if ($photo === "https://vk.com/images/deactivated_200.png"
                        or $photo === 'https://vk.com/images/camera_200.png?ava=1'
                        or $photo === null
                        or $bdate === null
                        // or $sex !== 2
                        // or new \DateTime($bdate) > new \DateTime('-30 years')
                    ) {
                        continue;
                    }

                    if (new \DateTime($bdate) > new \DateTime('-18 years')
                        and new \DateTime($bdate) < new \DateTime('-29 years')
                    ) {
                        continue;
                    }
                } catch (\Exception $e) {
                    continue;
                }

                $data[] = $photo;

                if (count($data) == 1000) {
                    break;
                }
            }
        }

        file_put_contents($this->kernel->getLogDir() . '/users_1000.json', json_encode($data));
    }
}
