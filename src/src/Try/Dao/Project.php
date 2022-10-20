<?php
namespace App\Try\Dao;

use Sebk\SmallOrmCore\Dao\AbstractDao;
use Sebk\SmallOrmCore\Dao\Field;

class Project extends AbstractDao
{
    protected function build()
    {
        $this->setDbTableName("project")
            ->setModelName("Project")
            ->addPrimaryKey("id", "id")
            ->addField("user_id", "userId", null, Field::TYPE_INT)
            ->addField("name", "name", null, Field::TYPE_STRING)
        ;
    }
}