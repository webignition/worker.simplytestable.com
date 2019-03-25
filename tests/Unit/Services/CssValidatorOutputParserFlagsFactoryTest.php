<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services;

use App\Entity\Task\Task;
use App\Model\Task\Type;
use App\Services\CssValidatorOutputParserFlagsFactory;
use webignition\CssValidatorOutput\Parser\Flags;

class CssValidatorOutputParserFlagsFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CssValidatorOutputParserFlagsFactory
     */
    private $cssValidatorOutputParserFlagsFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->cssValidatorOutputParserFlagsFactory = new CssValidatorOutputParserFlagsFactory();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(Task $task, int $expectedFlags)
    {
        $flags = $this->cssValidatorOutputParserFlagsFactory->create($task);

        $this->assertEquals($flags, $expectedFlags);
    }

    public function createDataProvider(): array
    {
        return [
            'no task parameters' => [
                'task' => $this->createTask([]),
                'expectedFlags' =>
                    Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS |
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
            ],
            'ignore warnings only' => [
                'task' => $this->createTask([
                    'ignore-warnings' => true,
                ]),
                'expectedFlags' =>
                    Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS |
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES |
                    Flags::IGNORE_WARNINGS,
            ],
            'vendor-extensions: ignore' => [
                'task' => $this->createTask([
                    'vendor-extensions' => 'ignore',
                ]),
                'expectedFlags' =>
                    Flags::IGNORE_VENDOR_EXTENSION_ISSUES |
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
            ],
            'vendor-extensions: error' => [
                'task' => $this->createTask([
                    'vendor-extensions' => 'error',
                ]),
                'expectedFlags' =>
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
            ],
            'vendor-extensions: warn' => [
                'task' => $this->createTask([
                    'vendor-extensions' => 'warn',
                ]),
                'expectedFlags' =>
                    Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS |
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
            ],
            'ignore warnings and vendor-extensions: ignore' => [
                'task' => $this->createTask([
                    'ignore-warnings' => true,
                    'vendor-extensions' => 'ignore',
                ]),
                'expectedFlags' =>
                    Flags::IGNORE_VENDOR_EXTENSION_ISSUES |
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES |
                    Flags::IGNORE_WARNINGS,
            ],
            'ignore warnings and vendor-extensions: error' => [
                'task' => $this->createTask([
                    'ignore-warnings' => true,
                    'vendor-extensions' => 'error',
                ]),
                'expectedFlags' =>
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES |
                    Flags::IGNORE_WARNINGS,
            ],
            'ignore warnings and vendor-extensions: warn' => [
                'task' => $this->createTask([
                    'ignore-warnings' => true,
                    'vendor-extensions' => 'warn',
                ]),
                'expectedFlags' =>
                    Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS |
                    Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES |
                    Flags::IGNORE_WARNINGS,
            ],
        ];
    }

    private function createTask(array $parameters): Task
    {
        $type = new Type('task type name', true, null);
        $url = 'http://example.com';

        Flags::NONE;
        Flags::IGNORE_WARNINGS;
        Flags::IGNORE_VENDOR_EXTENSION_ISSUES;
        Flags::IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES;
        Flags::REPORT_VENDOR_EXTENSION_ISSUES_AS_WARNINGS;

        return Task::create($type, $url, (string) json_encode($parameters));
    }
}
