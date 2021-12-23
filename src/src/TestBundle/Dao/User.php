<?php
namespace App\TestBundle\Dao;

use Sebk\SmallOrmCore\Dao\AbstractDao;

class User extends AbstractDao
{
    protected function build()
    {
        $this->setDbTableName("user")
            ->setModelName("User")
            ->addPrimaryKey("id", "id")
            ->addField("name", "name")
        ;
    }
}