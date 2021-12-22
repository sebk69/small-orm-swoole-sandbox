<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class ProjectRepository extends EntityRepository
{
    public function findAll()
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select(['p'])
            ->from(Project::class, 'p');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    public function listPaginated($page, $pageSize)
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select(['p'])
            ->from(Project::class, 'p');

        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $pageSize + 1)
            ->setMaxResults($pageSize)
        ;

        return $query->getResult();
    }


}
