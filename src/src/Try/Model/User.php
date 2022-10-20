<?php
namespace App\Try\Model;

use Sebk\SmallOrmCore\Dao\Model;

class User extends Model
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
     * @return string
     */
    public function getName()
    {
        return parent::getName();
    }
    
    // To many relations getters
    /**
     * @return Project
     */
    public function getProject()
    {
        return parent::getProject();
    }
    
    
    // Fields setters
    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        parent::setName($name);
        
        return $this;
    }
    
    // To many relations setters
    /**
     * @param Project $project
     * @return $this
     */
    public function addProject(Project $project)
    {
        parent::setProject($project);
        
        return $this;
    }
    
}