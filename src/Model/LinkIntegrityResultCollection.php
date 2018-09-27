<?php

namespace App\Model;

class LinkIntegrityResultCollection implements \Iterator, \JsonSerializable
{
    /**
     * @var LinkIntegrityResult[]
     */
    private $linkResults = [];

    /**
     * @var int
     */
    private $position = 0;

    public function __construct()
    {
        $this->position = 0;
    }

    public function add(LinkIntegrityResult $linkResult)
    {
        $this->linkResults[] = $linkResult;
    }

    public function getErrorCount(): int
    {
        $errorCount = 0;

        foreach ($this->linkResults as $linkResult) {
            if ($linkResult->getLinkState()->isError()) {
                $errorCount++;
            }
        }

        return $errorCount;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): LinkIntegrityResult
    {
        return $this->linkResults[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->linkResults[$this->position]);
    }

    public function jsonSerialize(): array
    {
        $data = [];

        foreach ($this as $linkResult) {
            $data[] = $linkResult;
        }

        return $data;
    }
}
