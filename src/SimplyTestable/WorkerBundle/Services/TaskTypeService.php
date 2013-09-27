<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type;


class TaskTypeService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\Task\Type\Type';
    const HTML_VALIDATION_NAME = 'HTML Validation';
    const CSS_VALIDATION_NAME = 'CSS Validation';
    const JS_STATIC_ANALYSIS_NAME = 'JS static analysis';
    const URL_DISCOVERY_NAME = 'URL discovery';
    const LINK_VERIFICATRION_NAME = 'Link verification';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }

    
    /**
     * @param string $name
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type\Type
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
    
    
    /**
     * 
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type\Type
     */
    public function getLinkVerificationTaskType() {
        return $this->fetch(self::LINK_VERIFICATRION_NAME);
    }    
    

    /**
     * 
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type\Type
     */
    public function getHtmlValidationTaskType() {
        return $this->fetch(self::HTML_VALIDATION_NAME);
    }
    
    /**
     * 
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type\Type
     */
    public function getCssValidationTaskType() {
        return $this->fetch(self::CSS_VALIDATION_NAME);
    }    
    

    /**
     * 
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type\Type
     */
    public function getJavaScriptStatisAnalysisTaskType() {
        return $this->fetch(self::JS_STATIC_ANALYSIS_NAME);
    }        
    
    /**
     * 
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type\Type
     */
    public function getUrlDiscoveryTaskType() {
        return $this->fetch(self::URL_DISCOVERY_NAME);
    }      
}