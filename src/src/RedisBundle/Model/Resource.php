<?php
namespace App\RedisBundle\Model;

use Sebk\SmallOrmCore\Dao\Model;

/**
 * @method getId()
 * @method setId($value)
 * @method getUserId()
 * @method setUserId($value)
 * @method getName()
 * @method setName($value)
 */
class Resource extends Model
{
    public function beforeSave()
    {
        $this->setKey($this->getId());
    }
}