<?php

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Model\Task\Type;
use App\Services\TaskTypePreparer\Factory;
use App\Services\TaskTypePreparer\LinkIntegrityTaskTypePreparer;
use App\Tests\Functional\AbstractBaseTestCase;

class FactoryTest extends AbstractBaseTestCase
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->factory = self::$container->get(Factory::class);
    }

    /**
     * @dataProvider getPreparerDataProvider
     *
     * @param string $taskType
     * @param null|string $expectedTaskTypePreparerClassName
     */
    public function testGetPreparer(string $taskType, ?string $expectedTaskTypePreparerClassName)
    {
        $taskTypePreparer = $this->factory->getPreparer($taskType);

        if (empty($expectedTaskTypePreparerClassName)) {
            $this->assertNull($taskTypePreparer);
        } else {
            $this->assertSame($expectedTaskTypePreparerClassName, get_class($taskTypePreparer));
        }
    }

    public function getPreparerDataProvider()
    {
        return [
            'html validation' => [
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedTaskTypePreparerClassName' => null,
            ],
            'css validation' => [
                'taskType' => Type::TYPE_CSS_VALIDATION,
                'expectedTaskTypePreparerClassName' => null,
            ],
            'link integrity' => [
                'taskType' => Type::TYPE_LINK_INTEGRITY,
                'expectedTaskTypePreparerClassName' => LinkIntegrityTaskTypePreparer::class,
            ],
            'url discovery' => [
                'taskType' => Type::TYPE_URL_DISCOVERY,
                'expectedTaskTypePreparerClassName' => null,
            ],
        ];
    }
}
