<?php

declare(strict_types=1);

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Security;

class MainMenu
{
    private FactoryInterface $factory;
    private Security $security;

    public function __construct(FactoryInterface $factory, Security $security)
    {
        $this->factory  = $factory;
        $this->security = $security;
    }

    /**
     * Главное меню
     */
    public function top(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes'    => [
                'class' => 'navbar-nav',
            ],
        ]);

        $menu->addChild('Homepage', ['route' => 'homepage'])
            ->setAttribute('class', 'nav-item')
            ->setLinkAttribute('class', 'nav-link py-0')
        ;

//        $menu->addChild('Stats', ['route' => 'stats'])
//            ->setAttribute('class', 'nav-item')
//            ->setLinkAttribute('class', 'nav-link py-0')
        ;

//        if ($this->security->getUser()->getStatus() == User::STATUS_PENDING) {
//            $menu->addChild('My profile', ['route' => 'profile'])
//                ->setAttribute('class', 'nav-item')
//                ->setLinkAttribute('class', 'nav-link py-0')
//            ;
//        }

//        if ($this->security->getUser()->isWitness()) {
//            $menu->addChild('Manage users', ['route' => 'admin'])
//                ->setAttribute('class', 'nav-item')
//                ->setLinkAttribute('class', 'nav-link py-0')
//            ;
//        }

        return $menu;
    }
}
