<?php
namespace App\TestBundle\Dao;

use Sebk\SmallOrmCore\Dao\AbstractDao;

class Project extends AbstractDao
{
    protected function build()
    {
        $this->setDbTableName("project")
            ->setModelName("Project")
            ->addPrimaryKey("id", "id")
            ->addField("user_id", "userId")
            ->addField("name", "name")
            ->addToOne("user", ["userId" => "id"], "User")
        ;
    }

    public function findPaginated()
    {
        $query = $this->createQueryBuilder("project");
        $query->innerJoin("project", "user");
        $query->paginate(1, 10);
        return $this->getResult($query);
    }
}