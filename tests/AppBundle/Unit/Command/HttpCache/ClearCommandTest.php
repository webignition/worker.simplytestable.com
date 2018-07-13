<?php

namespace Tests\AppBundle\Unit\Command\HttpCache;

use Mockery\Mock;
use SimplyTestable\AppBundle\Command\HttpCache\ClearCommand;
use SimplyTestable\AppBundle\Services\HttpCache;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/HttpCache/ClearCommand
 */
class ClearCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param bool $httpCacheClearReturnValue
     * @param int $expectedReturnCode
     * @throws \Exception
     */
    public function testRun($httpCacheClearReturnValue, $expectedReturnCode)
    {
        /* @var HttpCache|Mock $httpCache */
        $httpCache = \Mockery::mock(HttpCache::class);
        $httpCache
            ->shouldReceive('clear')
            ->andReturn($httpCacheClearReturnValue);

        $command = new ClearCommand($httpCache);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            $returnCode,
            $expectedReturnCode
        );
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'fail' => [
                'httpCacheClearReturnValue' => false,
                'expectedReturnCode' => 1,
            ],
            'success' => [
                'httpCacheClearReturnValue' => true,
                'expectedReturnCode' => 0,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
