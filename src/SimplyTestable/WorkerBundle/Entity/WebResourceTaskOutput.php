<?php
namespace SimplyTestable\WorkerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="WebResourceTaskOutput",
 *     indexes={
 *         @ORM\Index(name="hash_index", columns={"hash"})
 *     }
 * )
 */
class WebResourceTaskOutput
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
    protected $errorCount;
  

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
}