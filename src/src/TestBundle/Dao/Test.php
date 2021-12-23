<?php
namespace App\TestBundle\Dao;

use Sebk\SmallOrmCore\Dao\AbstractDao;
use Sebk\SmallOrmCore\Dao\Field;

class Test extends AbstractDao
{
    protected function build()
    {
        $this->setDbTableName("test")
            ->setModelName("Test")
            ->addPrimaryKey("int", "int")
            ->addField("varchar", "varchar", null, Field::TYPE_STRING)
            ->addField("datetime", "datetime", null, Field::TYPE_DATETIME)
            ->addField("date", "date", null, Field::TYPE_DATE)
            ->addField("json", "json", null, Field::TYPE_JSON)
            ->addField("decimal", "decimal", null, Field::TYPE_FLOAT)
            ->addField("bigint", "bigint", null, Field::TYPE_INT)
            ->addField("double", "double", null, Field::TYPE_INT)
            ->addField("float", "float", null, Field::TYPE_FLOAT)
            ->addField("mediumint", "mediumint", null, Field::TYPE_INT)
            ->addField("real", "real", null, Field::TYPE_INT)
            ->addField("smallint", "smallint", null, Field::TYPE_INT)
            ->addField("tinyint", "tinyint", null, Field::TYPE_BOOLEAN)
            ->addField("char", "char", null, Field::TYPE_STRING)
            ->addField("nchar", "nchar", null, Field::TYPE_STRING)
            ->addField("nvarchar", "nvarchar", null, Field::TYPE_STRING)
            ->addField("longtext", "longtext", null, Field::TYPE_STRING)
            ->addField("mediumtext", "mediumtext", null, Field::TYPE_STRING)
            ->addField("tinytext", "tinytext", null, Field::TYPE_STRING)
            ->addField("testcol", "testcol", null, Field::TYPE_BOOLEAN)
            ->addField("testcol1", "testcol1", null, Field::TYPE_STRING)
            ->addField("boolean", "boolean", null, Field::TYPE_INT)
        ;
    }

    public function test()
    {
        
    }
}