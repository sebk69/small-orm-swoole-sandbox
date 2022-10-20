<?php
namespace App\TestBundle\Dao;

use Sebk\SmallOrmCore\Dao\AbstractDao;

class User extends AbstractDao
{
    protected function build()
    {
        $this->setDbTableName("user")
            ->setModelClass(\App\TestBundle\Model\User::class)
            ->addPrimaryKey("id", "id")
            ->addField("name", "name")
            ->addToMany('projects', ['id' => 'userId'], \App\TestBundle\Model\Project::class)
        ;
    }
}