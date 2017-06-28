<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Memcached\HttpCache;

use Doctrine\Common\Cache\MemcachedCache;
use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Memcached\HttpCache\ClearCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
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
        $memcachedCache = \Mockery::mock(MemcachedCache::class);
        $memcachedCache
            ->shouldReceive('deleteAll')
            ->andReturn($deleteAllReturnValue);

        /* @var HttpClientService|MockInterface $httpClientService */
        $httpClientService = \Mockery::mock(HttpClientService::class);
        $httpClientService
            ->shouldReceive('getMemcachedCache')
            ->andReturn($memcachedCache);

        $command = new ClearCommand($httpClientService);

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

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
