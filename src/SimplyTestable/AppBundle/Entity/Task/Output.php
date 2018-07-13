<?php
namespace SimplyTestable\AppBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\AppBundle\Entity\State;
use webignition\InternetMediaType\InternetMediaType;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="TaskOutput"
 * )
 *
 */
class Output
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
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\AppBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $output;

    /**
     * @var InternetMediaType
     * @ORM\Column(type="text", nullable=true)
     */
    protected $contentType;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $errorCount = 0;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $warningCount = 0;

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
     * @param State $state
     *
     * @return $this
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
     * @param string $output
     *
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }


    /**
     * @param InternetMediaType $contentType
     *
     * @return $this
     */
    public function setContentType(InternetMediaType $contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return InternetMediaType
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param int $errorCount
     *
     * @return $this
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * @param integer $warningCount
     *
     * @return $this
     */
    public function setWarningCount($warningCount)
    {
        $this->warningCount = $warningCount;

        return $this;
    }

    /**
     * @return integer
     */
    public function getWarningCount()
    {
        return $this->warningCount;
    }
}
