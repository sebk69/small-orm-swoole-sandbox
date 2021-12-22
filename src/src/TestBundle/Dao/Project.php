<?php
namespace App\TestBundle\Dao;

use Sebk\SmallOrmCore\Dao\AbstractDao;

class Project extends AbstractDao
{
    protected function build()
    {
        $this->setDbTableName("project")
            ->setModelName("Resource")
            ->addPrimaryKey("id", "id")
            ->addField("user_id", "userId")
            ->addField("name", "name")
            ->addToOne("user", ["userId" => "id"], "User")
        ;
    }

    public function findPaginated($page = 1, $pageSize = 25)
    {
        $query = $this->createQueryBuilder("project");
        $query->paginate($page, $pageSize);
        return $this->getResult($query);
    }
}