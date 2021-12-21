<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class ProjectRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findAll()
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select(['p', 'u'])
            ->from(Project::class, 'p')
            ->leftJoin('p.users', 'u');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

}
