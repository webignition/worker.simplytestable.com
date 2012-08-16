<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type;


class TaskTypeService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\Task\Type\Type';
    const HTML_VALIDATION_NAME = 'HTML Validation';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }

    
    /**
     * @param string $name
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type
     */
    public function fetch($name) {        
        return $this->getEntityRepository()->findOneByName($name);
    }
    
    
    /**
     *
     * @param string $name
     * @return boolean
     */
    public function has($name) {
        return !is_null($this->fetch($name));
    }
    
    
    public function getHtmlValidationTaskType() {
        return $this->fetch(self::HTML_VALIDATION_NAME);
    }
}