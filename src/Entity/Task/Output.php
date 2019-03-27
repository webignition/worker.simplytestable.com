<?php

namespace App\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
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
     * @ORM\Column(type="text", nullable=false)
     */
    private $content = '';

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $contentType = '';

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
        string $content,
        InternetMediaTypeInterface $contentType,
        int $errorCount = 0,
        int $warningCount = 0
    ): Output {
        $output = new static();

        $output->content = $content;
        $output->contentType = $contentType;
        $output->errorCount = $errorCount;
        $output->warningCount = $warningCount;

        return $output;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
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
