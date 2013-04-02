<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication;
use SimplyTestable\WorkerBundle\Model\RemoteEndpoint;

class CoreApplicationService extends EntityService {    
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication';     
    
    private $remoteEndpoints = array(
        'worker-activate' => array(
            'url' => '/worker/activate/',
            'method' => \Guzzle\Http\Message\RequestInterface::POST
        )
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
     * @return CoreApplication
     */
    public function get() {
        return $this->getEntityRepository()->find(1);
    }
    
    /**
     *
     * @param CoreApplication $coreApplication
     * @return \SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication 
     */
    public function populateRemoteEndpoints(CoreApplication $coreApplication) {
        foreach ($this->remoteEndpoints as $identifier => $properties) {
            $url = $coreApplication->getUrl() . $properties['url'];
            
            $remoteEndpoint = new RemoteEndpoint();
            $remoteEndpoint->setIdentifier($identifier);
            $remoteEndpoint->setUrl($url);
            $remoteEndpoint->setMethod($properties['method']);
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