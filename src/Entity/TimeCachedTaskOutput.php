<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="TimeCachedTaskOutput",
 *     indexes={
 *         @ORM\Index(name="hash_index", columns={"hash"})
 *     }
 * )
 */
class TimeCachedTaskOutput
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
     * @ORM\Column(type="string")
     */
    protected $hash;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $output;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $errorCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $warningCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $maxAge = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $lastModified;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $hash
     *
     * @return $this
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
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
     * @param integer $errorCount
     *
     * @return $this
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    /**
     * @return integer
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


    /**
     * @param integer $maxAge
     *
     * @return $this
     */
    public function setMaxAge($maxAge)
    {
        $this->maxAge = $maxAge;

        return $this;
    }

    /**
     * @return integer
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }


    /**
     * @param \DateTime $lastModified
     *
     * @return $this
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }
}
