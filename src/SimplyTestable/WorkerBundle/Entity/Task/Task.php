<?php
namespace SimplyTestable\WorkerBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\WorkerBundle\Repository\TaskRepository")
 */
class Task implements \JsonSerializable
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    protected $url;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state;

    /**
     * @var TaskType
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     */
    protected $type;

    /**
     * @var TimePeriod
     *
     * @ORM\OneToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\TimePeriod", cascade={"persist"})
     */
    protected $timePeriod;

    /**
     * @var Output
     *
     * @ORM\OneToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\Task\Output", cascade={"persist"})
     */
    protected $output;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $parameters;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $url
     *
     * @return Task
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param State $state
     *
     * @return Task
     */
    public function setState(State $state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param TaskType $type
     *
     * @return Task
     */
    public function setType(TaskType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return TaskType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param TimePeriod $timePeriod
     *
     * @return Task
     */
    public function setTimePeriod(TimePeriod $timePeriod = null)
    {
        $this->timePeriod = $timePeriod;

        return $this;
    }

    /**
     * @return TimePeriod
     */
    public function getTimePeriod()
    {
        return $this->timePeriod;
    }

    /**
     * @param Output $output
     *
     * @return Task
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return Task
     */
    public function setNextState()
    {
        if (!is_null($this->getState()->getNextState())) {
            $this->state = $this->getState()->getNextState();
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasOutput()
    {
        return !is_null($this->output);
    }

    /**
     * @param string $parameters
     *
     * @return Task
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getParametersHash()
    {
        return md5($this->getParameters());
    }

    /**
     * @return boolean
     */
    public function hasParameters()
    {
        return $this->getParameters() != '';
    }

    /**
     *
     * @return array
     */
    public function getParametersArray()
    {
        return json_decode($this->getParameters(), true);
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasParameter($name)
    {
        $parameters = $this->getParametersArray();
        return isset($parameters[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        $parameters = $this->getParametersArray();
        return $parameters[$name];
    }

    /**
     * @param string $parameterName
     *
     * @return boolean
     */
    public function isTrue($parameterName)
    {
        if (!$this->hasParameter($parameterName)) {
            return false;
        }

        return filter_var($this->getParameter($parameterName), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'type' => (string)$this->getType(),
            'url' => $this->getUrl(),
        ];
    }
}
