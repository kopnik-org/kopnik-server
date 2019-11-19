<?php

declare(strict_types=1);

namespace App\Menu;

use App\Entity\User;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Security;

class MainMenu // implements ContainerAwareInterface
{
    //use ContainerAwareTrait;

    private $factory;
    private $security;

    /**
     * MainMenu constructor.
     *
     * @param FactoryInterface $factory
     * @param Security         $security
     */
    public function __construct(FactoryInterface $factory, Security $security)
    {
        $this->factory  = $factory;
        $this->security = $security;
    }

    /**
     * Главное меню
     *
     * @param array $options
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function top(array $options)
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

        $menu->addChild('Stats', ['route' => 'stats'])
            ->setAttribute('class', 'nav-item')
            ->setLinkAttribute('class', 'nav-link py-0')
        ;

        if ($this->security->getUser()->getStatus() == User::STATUS_PENDING) {
            $menu->addChild('My profile', ['route' => 'profile'])
                ->setAttribute('class', 'nav-item')
                ->setLinkAttribute('class', 'nav-link py-0')
            ;
        }

        if ($this->security->getUser()->isWitness()) {
            $menu->addChild('Manage users', ['route' => 'admin'])
                ->setAttribute('class', 'nav-item')
                ->setLinkAttribute('class', 'nav-link py-0')
            ;
        }

        return $menu;
    }
}
