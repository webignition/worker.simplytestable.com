<?php

namespace App\Entity\Task;

use App\Model\Task\Type;
use Doctrine\ORM\Mapping as ORM;
use App\Model\Task\Parameters;

/**
 * @ORM\Entity
 * @ORM\Table(name="Task")
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 */
class Task implements \JsonSerializable
{
    const STATE_QUEUED = 'queued';
    const STATE_PREPARING = 'preparing';
    const STATE_PREPARED = 'prepared';
    const STATE_IN_PROGRESS = 'in-progress';
    const STATE_COMPLETED = 'completed';
    const STATE_CANCELLED = 'cancelled';
    const STATE_FAILED_NO_RETRY_AVAILABLE = 'failed-no-retry-available';
    const STATE_FAILED_RETRY_AVAILABLE = 'failed-retry-available';
    const STATE_FAILED_RETRY_LIMIT_REACHED = 'failed-retry-limit-reached';
    const STATE_SKIPPED = 'skipped';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="state", nullable=false)
     */
    private $state;

    /**
     * @var Type
     *
     * @ORM\Column(name="tasktype", nullable=false)
     */
    private $type;

    /**
     * @var Output
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Task\Output", cascade={"persist"})
     */
    private $output;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $parameters;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDateTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endDateTime;

    /**
     * @var Task
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Task\Task")
     * @ORM\JoinColumn(name="parent_task_id", referencedColumnName="id", nullable=true)
     */
    private $parentTask;

    /**
     * @var Parameters
     */
    private $parametersObject;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setState(string $state)
    {
        $this->state = $state;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setOutput(Output $output)
    {
        $this->output = $output;
    }

    public function getOutput(): Output
    {
        return $this->output;
    }

    public function hasOutput(): bool
    {
        return !is_null($this->output);
    }

    public function setParameters(string $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameters(): string
    {
        return $this->parameters;
    }

    public function getParametersHash(): string
    {
        return md5($this->getParameters());
    }

    public function getParametersArray(): array
    {
        $decodedParameters = json_decode($this->getParameters(), true);

        if (!is_array($decodedParameters)) {
            $decodedParameters = [];
        }

        return $decodedParameters;
    }

    public function getParametersObject(): Parameters
    {
        if (empty($this->parametersObject)) {
            $parametersArray = json_decode($this->getParameters(), true) ?? [];

            $this->parametersObject = new Parameters($parametersArray, $this->url);
        }

        return $this->parametersObject;
    }

    public function getParameter(string $name)
    {
        return $this->getParametersArray()[$name] ?? null;
    }

    public function setParentTask(Task $parentTask)
    {
        $this->parentTask = $parentTask;
    }

    public function getParentTask(): ?Task
    {
        return $this->parentTask;
    }

    public function setStartDateTime(\DateTime $startDateTime)
    {
        $this->startDateTime = $startDateTime;
    }

    public function getStartDateTime(): \DateTime
    {
        return $this->startDateTime;
    }

    public function setEndDateTime(\DateTime $endDateTime)
    {
        $this->endDateTime = $endDateTime;
    }

    public function getEndDateTime(): ?\DateTime
    {
        return $this->endDateTime;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'url' => $this->getUrl(),
        ];
    }
}
