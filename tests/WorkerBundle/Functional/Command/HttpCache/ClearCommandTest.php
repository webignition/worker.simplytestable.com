<?php

namespace Tests\WorkerBundle\Functional\Command\HttpCache;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\HttpCache\ClearCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class ClearCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param bool $httpCacheClearReturnValue
     * @param int $expectedReturnCode
     */
    public function testRun($httpCacheClearReturnValue, $expectedReturnCode)
    {
        /* @var HttpCache|MockInterface $httpCache */
        $httpCache = \Mockery::mock(HttpCache::class);
        $httpCache
            ->shouldReceive('clear')
            ->andReturn($httpCacheClearReturnValue);

        $command = new ClearCommand($httpCache);

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
