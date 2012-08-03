<?php

namespace SimplyTestable\WorkerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
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
     */
    protected $url;
    
    
    /**
     *
     * @var SimplyTestable\WorkerBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     */    
    protected $state;

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
     * @return ThisWorker
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
}