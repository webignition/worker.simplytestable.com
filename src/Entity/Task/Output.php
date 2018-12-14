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

    public static function create(
        ?string $content = null,
        ?InternetMediaTypeInterface $contentType = null,
        ?int $errorCount = 0,
        ?int $warningCount = 0
    ): Output {
        $output = new static();

        $output->output = $content;
        $output->contentType = $contentType;
        $output->errorCount = $errorCount;
        $output->warningCount = $warningCount;

        return $output;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function getContentType(): string
    {
        return (string)$this->contentType;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }
}
