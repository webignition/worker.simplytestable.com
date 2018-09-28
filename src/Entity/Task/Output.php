<?php

namespace App\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="TaskOutput")
 */
class Output
{
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $output;

    /**
     * @var InternetMediaType
     * @ORM\Column(type="text", nullable=true)
     */
    private $contentType;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $errorCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $warningCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setOutput(?string $output)
    {
        $this->output = $output;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function setContentType(InternetMediaTypeInterface $contentType)
    {
        $this->contentType = $contentType;
    }

    public function getContentType(): InternetMediaTypeInterface
    {
        return $this->contentType;
    }

    public function setErrorCount(int $errorCount)
    {
        $this->errorCount = $errorCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function setWarningCount(int $warningCount)
    {
        $this->warningCount = $warningCount;
    }

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }
}
