<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Memcache\HttpCache;

use SimplyTestable\WorkerBundle\Command\Memcache\HttpCache\ClearCommand;
use SimplyTestable\WorkerBundle\Tests\Functional\Command\ConsoleCommandBaseTestCase;

class ClearCommandTest extends ConsoleCommandBaseTestCase
{
    protected function getAdditionalCommands()
    {
        return array(
            new ClearCommand(),
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $deleteAllReturnValue
     * @param int $expectedReturnValue
     *
     * @TODO: redmine-1013
     */
    public function testExecute($deleteAllReturnValue, $expectedReturnValue)
    {
        $this->assertEquals(
            0,
            $this->executeCommand('simplytestable:memcache:httpcache:clear')
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
                'expectedReturnValue' => 1,
            ],
            'success' => [
                'deleteAllReturnValue' => true,
                'expectedReturnValue' => 0,
            ],
        ];
    }
}
