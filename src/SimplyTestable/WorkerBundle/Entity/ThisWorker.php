<?php

namespace SimplyTestable\WorkerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class ThisWorker
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
     * @SerializerAnnotation\Expose
     */
    protected $hostname;
    
    
    /**
     *
     * @var SimplyTestable\WorkerBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Expose
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     * @SerializerAnnotation\Type("string")
     */    
    protected $state;
    
    
    /**
     *
     * @var string
     * 
     * @ORM\Column(type="string", nullable=true)
     */
    protected $activationToken;

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
     * @param string $hostname
     * @return ThisWorker
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Set state
     *
     * @param SimplyTestable\WorkerBundle\Entity\State $state
     * @return ThisWorker
     */
    public function setState(\SimplyTestable\WorkerBundle\Entity\State $state)
    {
        $this->state = $state;
    
        return $this;
    }

    /**
     * Get state
     *
     * @return SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set activationToken
     *
     * @param string $activationToken
     * @return ThisWorker
     */
    public function setActivationToken($activationToken)
    {
        $this->activationToken = $activationToken;
    
        return $this;
    }

    /**
     * Get activationToken
     *
     * @return string 
     */
    public function getActivationToken()
    {
        return $this->activationToken;
    }
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\ThisWorker
     */
    public function setNextState() {
        $this->state = $this->getState()->getNextState();
        return $this;
    }    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedState() {
        return str_replace('worker-', '', (string)$this->getState());
    }     
}