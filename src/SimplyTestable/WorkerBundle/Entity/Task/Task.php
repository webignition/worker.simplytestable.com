<?php
namespace SimplyTestable\WorkerBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 * @ORM\Entity(repositoryClass="SimplyTestable\WorkerBundle\Repository\TaskRepository")
 */
class Task
{
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SerializerAnnotation\Expose
     * 
     */
    protected $id;

    
    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     * @SerializerAnnotation\Expose
     * 
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
     * @SerializerAnnotation\Expose
     * @SerializerAnnotation\Type("string")
     */
    protected $state;
    
    
    /**
     *
     * @var SimplyTestable\WorkerBundle\Entity\Task\Type\Type
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedType")
     * @SerializerAnnotation\Expose
     * @SerializerAnnotation\Type("string")
     */
    protected $type;
    
    /**
     *
     * @var SimplyTestable\WorkerBundle\Entity\TimePeriod
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\TimePeriod", cascade={"persist"})
     * @SerializerAnnotation\Expose
     * 
     */
    protected $timePeriod;
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Entity\Task\Output
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\Task\Output", cascade={"persist"})
     * @SerializerAnnotation\Expose
     * 
     */
    protected $output;
    
    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     * @SerializerAnnotation\Expose
     * 
     */
    protected $parameters;    
    
    
    
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
     * Set url
     *
     * @param string $url
     * @return Task
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
     * Set type
     *
     * @param SimplyTestable\WorkerBundle\Entity\Task\Type\Type $type
     * @return Task
     */
    public function setType(\SimplyTestable\WorkerBundle\Entity\Task\Type\Type $type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Type\Type 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set timePeriod
     *
     * @param SimplyTestable\WorkerBundle\Entity\TimePeriod $timePeriod
     * @return Task
     */
    public function setTimePeriod(\SimplyTestable\WorkerBundle\Entity\TimePeriod $timePeriod = null)
    {
        $this->timePeriod = $timePeriod;
    
        return $this;
    }

    /**
     * Get timePeriod
     *
     * @return SimplyTestable\WorkerBundle\Entity\TimePeriod 
     */
    public function getTimePeriod()
    {
        return $this->timePeriod;
    }
    
    /**
     * Set output
     *
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Output $output
     * @return Task
     */
    public function setOutput(\SimplyTestable\WorkerBundle\Entity\Task\Output $output)
    {
        $this->output = $output;
    
        return $this;
    }

    /**
     * Get output
     *
     * @return SimplyTestable\WorkerBundle\Entity\Task\Output 
     */
    public function getOutput()
    {
        return $this->output;
    }    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    public function setNextState() {
        if (!is_null($this->getState()->getNextState())) {
            $this->state = $this->getState()->getNextState();
        }        
        
        return $this;
    }  
    
    /**
     *
     * @return boolean
     */
    public function hasOutput() {
        return !is_null($this->output);
    }
    
    /**
     * Set parameters
     *
     * @param string $parameters
     * @return Task
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    
        return $this;
    }

    /**
     * Get parameters
     *
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    } 
    
    
    /**
     * 
     * @return string
     */
    public function getParametersHash() {
        return md5($this->getParameters());
    }
    
    /**
     * 
     * @return boolean
     */
    public function hasParameters() {
        return $this->getParameters() != '';
    } 
    
    
    /**
     * 
     * @return \stdClass
     */
    public function getParametersArray() {
        return json_decode($this->getParameters(), true);
    }
    
    
    /**
     * 
     * @param string $name
     * @return boolean
     */
    public function hasParameter($name) {
        $parameters = $this->getParametersArray();
        return isset($parameters[$name]);
    }
    
    
    /**
     * 
     * @param string $name
     * @return mixed
     */
    public function getParameter($name) {
        if (!$this->hasParameter($name)) {
            return null;
        }
        
        $parameters = $this->getParametersArray();
        return $parameters[$name];
    }
    
    
    /**
     * 
     * @param string $parameterName
     * @return boolean
     */
    public function isTrue($parameterName) {
        if (!$this->hasParameter($parameterName)) {
            return false;
        }        
                
        return filter_var($this->getParameter($parameterName), FILTER_VALIDATE_BOOLEAN);
    }
}