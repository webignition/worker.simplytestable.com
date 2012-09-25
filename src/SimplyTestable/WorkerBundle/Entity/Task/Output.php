<?php
namespace SimplyTestable\WorkerBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use webignition\InternetMediaType\InternetMediaType;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="TaskOutput"
 * )
 * 
 */
class Output
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
     * @var SimplyTestable\WorkerBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     */
    protected $state;
    
    
    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $output;
    
    
    /**
     *
     * @var \webignition\InternetMediaType\InternetMediaType 
     * @ORM\Column(type="text", nullable=true)
     */
    protected $contentType;
    
    
    /**
     *
     * @var int 
     * @ORM\Column(type="integer", nullable=false)
     */
    private $errorCount;
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedState() {
        return str_replace('task-', '', (string)$this->getState());
    }  
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedType() {
        return (string)$this->getType();
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
     * Set state
     *
     * @param SimplyTestable\WorkerBundle\Entity\State $state
     * @return Task
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
     * Set output
     *
     * @param string $output
     * @return Task
     */
    public function setOutput($output)
    {
        $this->output = $output;
    
        return $this;
    }

    /**
     * Get output
     *
     * @return string 
     */
    public function getOutput()
    {
        return $this->output;
    } 
    
    
    /**
     *
     * @param InternetMediaType $contentType
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Output 
     */
    public function setContentType(InternetMediaType $contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }
    
    
    /**
     *
     * @return \webignition\InternetMediaType\InternetMediaType 
     */
    public function getContentType()
    {
        return $this->contentType;
    }
    
    /**
     * Set output
     *
     * @param int $errorCount
     * @return Output
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;
    
        return $this;
    }

    /**
     * Get error count
     *
     * @return int 
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }     
}