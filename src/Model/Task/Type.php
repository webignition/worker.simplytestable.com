<?php

namespace App\Model\Task;

class Type implements TypeInterface, \JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isSelectable;

    /**
     * @var Type|null
     */
    private $childType;

    public function __construct(string $name, bool $isSelectable, ?Type $childType)
    {
        $this->name = $name;
        $this->isSelectable = $isSelectable;
        $this->childType = $childType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSelectable(): bool
    {
        return $this->isSelectable;
    }

    public function getChildType(): ?Type
    {
        return $this->childType;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
