<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class RankListener implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        return $this->updateRank($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        return $this->updateRank($args);
    }

    public function updateRank(LifecycleEventArgs $args)
    {
        $user = $args->getObject();

        if (!$user instanceof User) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $changes = $uow->getEntityChangeSet($user);

        if (isset($changes['foreman'][0]) or isset($changes['foreman'][1])) {
            $this->updateRankForDescendantUser($em, $user);

            if ($changes['foreman'][0] instanceof User and is_null($changes['foreman'][1])) {
                /** @var User $foreman */
                $foreman = $changes['foreman'][0];

                $this->updateRankForDescendantUser($em, $foreman);
            }
        }
    }

    protected function updateRankForDescendantUser(EntityManagerInterface $em, User $user)
    {
        $descendantId = $user->getId();

        // @todo вынести в репу
        $sql = "
            UPDATE users
            SET rank = uc.cnt
            FROM (
                SELECT ucd.ancestor, COUNT (ucd.ancestor) AS cnt
                FROM users_closure AS ucd
                JOIN users_closure AS uca ON ucd.ancestor = uca.ancestor
                WHERE ucd.descendant = $descendantId
                GROUP BY ucd.ancestor
            ) AS uc
            WHERE id = uc.ancestor
        ";

        if (!$em->getConnection()->executeQuery($sql)) {
            throw new \RuntimeException('Failed to update ranks');
        }
    }
}
