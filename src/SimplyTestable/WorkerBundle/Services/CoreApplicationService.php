<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication;
use SimplyTestable\WorkerBundle\Model\RemoteEndpoint;

class CoreApplicationService extends EntityService {    
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication';     
    
    private $remoteEndpoints = array(
        'worker-activate' => '/worker/activate/'
    );
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     *
     * @return array
     */
    public function findAll() {
        return $this->getEntityRepository()->findAll();
    }
    
    /**
     *
     * @param CoreApplication $coreApplication
     * @return \SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication 
     */
    public function populateRemoteEndpoints(CoreApplication $coreApplication) {
        foreach ($this->remoteEndpoints as $identifier => $url) {
            $url = $coreApplication->getUrl() . $url;
            
            $remoteEndpoint = new RemoteEndpoint();
            $remoteEndpoint->setIdentifier($identifier);
            $remoteEndpoint->setUrl($url);
            $coreApplication->addRemoteEndpoint($remoteEndpoint);
        }
        
        return $coreApplication;
    }
    
    
    /**
     *
     * @param CoreApplication $coreApplication
     * @return CoreApplication
     */
    public function persistAndFlush(CoreApplication $coreApplication) {
        $this->getEntityManager()->persist($coreApplication);
        $this->getEntityManager()->flush();
        return $coreApplication;
    }
}