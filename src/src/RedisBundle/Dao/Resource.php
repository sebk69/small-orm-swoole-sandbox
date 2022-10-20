<?php
namespace App\RedisBundle\Dao;

use Sebk\SmallOrmCore\Dao\AbstractRedisDao;

class Resource extends AbstractRedisDao
{
    protected function build()
    {
        $this->setDbTableName("resource")
            ->setModelClass(\App\RedisBundle\Model\Resource::class)
            ->addField("id", "id")
            ->addField("name", "name")
        ;
    }
}