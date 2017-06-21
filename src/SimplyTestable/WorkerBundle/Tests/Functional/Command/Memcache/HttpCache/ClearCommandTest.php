<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Memcache\HttpCache;

use Memcache;
use SimplyTestable\WorkerBundle\Command\Memcache\HttpCache\ClearCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\MemcacheService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class ClearCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $deleteAllReturnValue
     * @param int $expectedReturnCode
     */
    public function testExecute($deleteAllReturnValue, $expectedReturnCode)
    {
        $memcache = \Mockery::mock(Memcache::class);
        $memcache
            ->shouldReceive('get')
            ->andReturn(false);

        $memcache
            ->shouldReceive('set')
            ->andReturn($deleteAllReturnValue);

        $memcacheService = \Mockery::mock(MemcacheService::class);
        $memcacheService
            ->shouldReceive('get')
            ->andReturn($memcache);

        $command = new ClearCommand($memcacheService);

        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            $returnCode,
            $expectedReturnCode
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider()
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
