<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Unit\Command\HttpCache;

use Doctrine\Common\Cache\MemcachedCache;
use App\Command\HttpCache\ClearCommand;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/HttpCache/ClearCommand
 */
class ClearCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider runDataProvider
     */
    public function testRun(bool $cachedDeleteAllReturnValue, int $expectedReturnCode)
    {
        $memcachedCache = \Mockery::mock(MemcachedCache::class);
        $memcachedCache
            ->shouldReceive('deleteAll')
            ->andReturn($cachedDeleteAllReturnValue);

        $command = new ClearCommand($memcachedCache);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            $returnCode,
            $expectedReturnCode
        );
    }

    public function runDataProvider(): array
    {
        return [
            'fail' => [
                'cachedDeleteAllReturnValue' => false,
                'expectedReturnCode' => 1,
            ],
            'success' => [
                'cachedDeleteAllReturnValue' => true,
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
