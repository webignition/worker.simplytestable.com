<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;

class OutputTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateNoErrorCountNoWarningCount()
    {
        $output = Output::create('', new InternetMediaType());

        $this->assertEquals(0, $output->getErrorCount());
        $this->assertEquals(0, $output->getWarningCount());
    }

    public function testCreateNoWarningCount()
    {
        $output = Output::create('', new InternetMediaType(), 1);

        $this->assertEquals(1, $output->getErrorCount());
        $this->assertEquals(0, $output->getWarningCount());
    }
}
