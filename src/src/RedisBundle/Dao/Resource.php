<?php
namespace App\RedisBundle\Dao;

use Sebk\SmallOrmCore\Dao\AbstractRedisDao;

class Resource extends AbstractRedisDao
{
    protected function build()
    {
        $this->setDbTableName("resource")
            ->setModelName("Resource")
            ->addField("id", "id")
            ->addField("name", "name")
        ;
    }
}