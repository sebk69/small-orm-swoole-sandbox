<?php
namespace App\Try\Model;

use Sebk\SmallOrmCore\Dao\Model;

class Project extends Model
{
    
    public function onLoad() {}
    
    public function beforeSave() {}
    
    public function afterSave() {}
    
    public function beforeDelete() {}
    
    public function afterDelete() {}
    
    // Fields getters
    /**
     * @return int
     */
    public function getId()
    {
        return parent::getId();
    }
    
    /**
     * @return int
     */
    public function getUserId()
    {
        return parent::getUserId();
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return parent::getName();
    }
    
    // To one relations getters
    /**
     * @return User
     */
    public function getUser()
    {
        return parent::getUser();
    }
    
    
    // Fields setters
    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        parent::setUserId($userId);
        
        return $this;
    }
    
    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        parent::setName($name);
        
        return $this;
    }
    
    // To one relations setters
    /**
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        parent::setUser($user);
        
        return $this;
    }
    
    
}