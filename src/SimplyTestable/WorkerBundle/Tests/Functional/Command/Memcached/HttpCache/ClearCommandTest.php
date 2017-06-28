<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Memcached\HttpCache;

use Memcached;
use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Memcached\HttpCache\ClearCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\MemcachedService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class ClearCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param bool $deleteAllReturnValue
     * @param int $expectedReturnCode
     */
    public function testRun($deleteAllReturnValue, $expectedReturnCode)
    {
        $memcached = \Mockery::mock(Memcached::class);
        $memcached
            ->shouldReceive('get')
            ->andReturn(false);

        $memcached
            ->shouldReceive('set')
            ->andReturn($deleteAllReturnValue);

        /* @var MemcachedService|MockInterface $memcachedService */
        $memcachedService = \Mockery::mock(MemcachedService::class);
        $memcachedService
            ->shouldReceive('get')
            ->andReturn($memcached);

        $command = new ClearCommand($memcachedService);

        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

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
                'deleteAllReturnValue' => false,
                'expectedReturnCode' => 1,
            ],
            'success' => [
                'deleteAllReturnValue' => true,
                'expectedReturnCode' => 0,
            ],
        ];
    }
}
