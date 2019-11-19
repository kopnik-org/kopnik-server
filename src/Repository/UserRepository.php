<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Smart\CoreBundle\Doctrine\RepositoryTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
    public function __construct(RegistryInterface $registry)
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
//            'is_confirmed' => true,
            'latitude' => 'not null',
            'longitude' => 'not null',
        ]);

        return $q->getResult();
    }
}