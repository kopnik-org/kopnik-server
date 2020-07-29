<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;
use Smart\CoreBundle\Doctrine\RepositoryTrait;

class UserRepository extends ClosureTreeRepository
{
    use RepositoryTrait\CountBy;
    use RepositoryTrait\FindByQuery;

    /**
     * @return User[]|array
     *
     * @deprecated
     */
    public function findNear($user): array
    {
        $q = $this->getFindByQuery([
            'status' => User::STATUS_CONFIRMED,
            'latitude' => 'not null',
            'longitude' => 'not null',
        ]);

        return $q->getResult();
    }

    /**
     * @return User[]
     */
    public function findByCoordinates($x1, $y1, $x2, $y2, $count): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.latitude > :y1')
            ->andWhere('e.latitude < :y2')
            ->andWhere('e.longitude > :x1')
            ->andWhere('e.longitude < :x2')
            ->andWhere('e.status = :status')
            ->orderBy('e.rank', 'DESC')
            ->setParameter('x1', $x1)
            ->setParameter('x2', $x2)
            ->setParameter('y1', $y1)
            ->setParameter('y2', $y2)
            ->setParameter('status', User::STATUS_CONFIRMED)
            ->setMaxResults((int) $count)
        ->getQuery()
        ->getResult();
    }

    public function findByEmail(string $email, string $provider = 'vkontakte'): ?User
    {
        return $this->createQueryBuilder('e')
            ->join('e.oauths', 'oa')
            ->where('oa.email = :email')
            ->andWhere('oa.provider = :provider')
            ->setParameter('email', $email)
            ->setParameter('provider', $provider)
        ->getQuery()
        ->getOneOrNullResult();
    }
}
