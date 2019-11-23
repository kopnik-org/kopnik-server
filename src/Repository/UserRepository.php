<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Smart\CoreBundle\Doctrine\RepositoryTrait;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    use RepositoryTrait\CountBy;
    use RepositoryTrait\FindByQuery;

    /**
     * UserRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param $user
     *
     * @return User[]|array
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
}
