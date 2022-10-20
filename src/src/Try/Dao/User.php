<?php
namespace App\Try\Dao;

use Sebk\SmallOrmCore\Dao\AbstractDao;
use Sebk\SmallOrmCore\Dao\Field;

class User extends AbstractDao
{
    protected function build()
    {
        $this->setDbTableName("user")
            ->setModelName("User")
            ->addPrimaryKey("id", "id")
            ->addField("name", "name", null, Field::TYPE_STRING)
        ;
    }
}