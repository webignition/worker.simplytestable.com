<?php
namespace App\Entity\Task\Type;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="TaskType")
  */
class Type
{
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
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    protected $description;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="selectable", nullable=false)
     */
    protected $selectable = false;

    public function __construct()
    {
        $this->selectable = false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }


    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setSelectable(bool $selectable)
    {
        $this->selectable = $selectable;
    }

    public function getSelectable(): bool
    {
        return $this->selectable;
    }

    public function equals(Type $taskType): bool
    {
        return $this->getName() == $taskType->getName();
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
