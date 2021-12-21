<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class UserRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findAll()
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select(['u', 'p'])
            ->from(User::class, 'u')
            ->leftJoin('u.projects', 'p');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

}
