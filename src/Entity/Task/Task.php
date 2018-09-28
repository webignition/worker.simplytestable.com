<?php

namespace App\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\TimePeriod;
use App\Model\Task\Parameters;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task"
 * )
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 */
class Task implements \JsonSerializable
{
    const STATE_QUEUED = 'queued';
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
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="state", nullable=false)
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(name="tasktype", nullable=false)
     */
    protected $type;

    /**
     * @var TimePeriod
     *
     * @ORM\OneToOne(targetEntity="App\Entity\TimePeriod", cascade={"persist"})
     */
    protected $timePeriod;

    /**
     * @var Output
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Task\Output", cascade={"persist"})
     */
    protected $output;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $parameters;

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

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setTimePeriod(?TimePeriod $timePeriod)
    {
        $this->timePeriod = $timePeriod;
    }

    public function getTimePeriod(): TimePeriod
    {
        return $this->timePeriod;
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

    public function hasParameters(): bool
    {
        return $this->getParameters() != '';
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
            $this->parametersObject = new Parameters($this);
        }

        return $this->parametersObject;
    }

    public function hasParameter(string $name): bool
    {
        $parameters = $this->getParametersArray();

        return isset($parameters[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter(string $name)
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        $parameters = $this->getParametersArray();

        return $parameters[$name];
    }

    public function isTrue(string $parameterName): bool
    {
        if (!$this->hasParameter($parameterName)) {
            return false;
        }

        return filter_var($this->getParameter($parameterName), FILTER_VALIDATE_BOOLEAN);
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
