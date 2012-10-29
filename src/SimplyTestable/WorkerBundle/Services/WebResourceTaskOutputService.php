<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\WebResourceTaskOutput;


class WebResourceTaskOutputService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\WebResourceTaskOutput';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     * 
     * @param string $hash
     * @return WebResourceTaskOutput
     */
    public function find($hash) {
        return $this->getEntityRepository()->findOneBy(array(
            'hash' => $hash
        ));
    }

    
    
    /**
     *
     * @param string $hash
     * @return boolean
     */
    public function has($hash) {
        return !is_null($this->find($hash));
    }
    
    
    /**
     *
     * @param string $name
     * @param string $output
     * @param int $errorCount
     * @return WebResourceTaskOutput
     */
    public function create($hash, $output, $errorCount) {
        $webResourceTaskOutput = new WebResourceTaskOutput();
        $webResourceTaskOutput->setHash($hash);
        $webResourceTaskOutput->setOutput($output);
        $webResourceTaskOutput->setErrorCount($errorCount);
        
        return $this->persistAndFlush($webResourceTaskOutput);
    }
    
    /**
     *
     * @param WebResourceTaskOutput $output
     * @return WebResourceTaskOutput
     */
    public function persistAndFlush(WebResourceTaskOutput $output) {
        $this->getEntityManager()->persist($output);
        $this->getEntityManager()->flush();
        return $output;
    }    
}