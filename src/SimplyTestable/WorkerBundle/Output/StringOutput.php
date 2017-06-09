<?php

namespace SimplyTestable\WorkerBundle\Output;

use Symfony\Component\Console\Output\Output;

class StringOutput extends Output
{
    /**
     * @var string
     */
    private $buffer = '';

    /**
     * {@inheritdoc}
     */
    public function doWrite($message, $newline)
    {
        $this->buffer .= $message.(true === $newline ? PHP_EOL : '');
    }

   /**
    * @return string
    */
    public function getBuffer()
    {
        return $this->buffer;
    }
}
