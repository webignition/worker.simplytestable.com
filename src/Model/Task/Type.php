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

    public function __construct(string $name, bool $isSelectable)
    {
        $this->name = $name;
        $this->isSelectable = $isSelectable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSelectable(): bool
    {
        return $this->isSelectable;
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
