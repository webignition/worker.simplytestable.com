<?php
namespace SimplyTestable\WorkerBundle\Entity\CoreApplication;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use SimplyTestable\WorkerBundle\Model\RemoteEndpoint;

/**
 * 
 * @ORM\Entity
 */
class CoreApplication
{    
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    
    /**
     *
     * @var string
     * 
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $url;
    
    
    /**
     * 
     * @var Collection
     */
    private $remoteEndpoints;
    
    
    /**
     *
     * @return array
     */
    public function getRemoteEndpoints() {        
        return $this->remoteEndpoints()->toArray();
    }
    
    
    /**
     *
     * @param RemoteEndpoint $remoteEndpoint 
     */
    public function addRemoteEndpoint(RemoteEndpoint $remoteEndpoint) {
        if (!$this->hasRemoteEndpoint($remoteEndpoint)) {
            $this->remoteEndpoints()->set($remoteEndpoint->getIdentifier(), $remoteEndpoint);
        }
    }
    
    
    /**
     *
     * @param RemoteEndpoint $remoteEndpoint 
     */
    public function removeRemoteEndpoint(RemoteEndpoint $remoteEndpoint) {
        if (!$this->hasRemoteEndpoint($remoteEndpoint)) {
            unset($this->remoteEndpoints[$remoteEndpoint->getIdentifier()]);
        }        
    }
    
    
    /**
     *
     * @param RemoteEndpoint $remoteEndpoint
     * @return RemoteEndpoint
     */
    public function getRemoteEndpoint(RemoteEndpoint $remoteEndpoint) {
        if ($this->hasRemoteEndpoint($remoteEndpoint)) {
            return $this->remoteEndpoints[$remoteEndpoint->getIdentifier()];
        }
        
        throw new Exception('Remote endpoint "'.$remoteEndpoint->getIdentifier().'" not found', 1);
    }
    
    /**
     *
     * @param RemoteEndpoint $remoteEndpoint
     * @return boolean
     */
    public function hasRemoteEndpoint(RemoteEndpoint $remoteEndpoint) {
        return $this->remoteEndpoints()->containsKey($remoteEndpoint->getIdentifier());
    }
    
    
    /**
     *
     * @return ArrayCollection
     */
    private function remoteEndpoints() {
        if (is_null($this->remoteEndpoints)) {
            $this->remoteEndpoints = new ArrayCollection();
        }
        
        return $this->remoteEndpoints;
    }
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return CoreApplication
     */
    public function setUrl($url)
    {
        $this->url = $url;
    
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }
}