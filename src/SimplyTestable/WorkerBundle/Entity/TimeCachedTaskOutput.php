<?php
namespace SimplyTestable\WorkerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
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
     * @ORM\Column(type="string")
     */
    protected $hash;
    
    
    /**
     *
     * @var string
     * 
     * @ORM\Column(type="text")
     */
    protected $output;
    
    
    /**
     *
     * @var int
     * 
     * @ORM\Column(type="integer")
     */
    protected $errorCount = 0;
    
    
    /**
     *
     * @var int
     * 
     * @ORM\Column(type="integer")
     */
    protected $warningCount = 0;
    
    
    /**
     * 
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     */
    protected $maxAge = 0;
    
    
    /**
     *
     * @var \DateTime
     * 
     * @ORM\Column(type="datetime")
     */
    protected $lastModified;
  

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
     * Set hash
     *
     * @param string $hash
     * @return WebResourceTaskOutput
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    
        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set output
     *
     * @param string $output
     * @return WebResourceTaskOutput
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
     * Set errorCount
     *
     * @param integer $errorCount
     * @return WebResourceTaskOutput
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;
    
        return $this;
    }

    /**
     * Get errorCount
     *
     * @return integer 
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }
    
    
    /**
     * Set warningCount
     *
     * @param integer $warningCount
     * @return WebResourceTaskOutput
     */
    public function setWarningCount($warningCount)
    {
        $this->warningCount = $warningCount;
    
        return $this;
    }

    /**
     * Get warningCount
     *
     * @return integer 
     */
    public function getWarningCount()
    {
        return $this->warningCount;
    } 
    
    
    /**
     * Set maxAge
     *
     * @param integer $maxAge
     * @return WebResourceTaskOutput
     */
    public function setMaxAge($maxAge)
    {
        $this->maxAge = $maxAge;
    
        return $this;
    }

    /**
     * Get maxAge
     *
     * @return integer 
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }
    
    
    /**
     * Set lastModified
     *
     * @param \DateTime $lastModified
     * @return TimePeriod
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    
        return $this;
    }

    /**
     * Get lastModified
     *
     * @return \DateTime 
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }    
}