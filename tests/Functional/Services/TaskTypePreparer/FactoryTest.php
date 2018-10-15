<?php

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Model\Task\Type;
use App\Services\TaskTypePreparer\Factory;
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
     */
    public function testGetPreparer(string $taskType)
    {
        $this->assertNull($this->factory->getPreparer($taskType));
    }

    public function getPreparerDataProvider()
    {
        return [
            'html validation' => [
                'taskType' => Type::TYPE_HTML_VALIDATION,
            ],
            'css validation' => [
                'taskType' => Type::TYPE_CSS_VALIDATION,
            ],
            'link integrity' => [
                'taskType' => Type::TYPE_LINK_INTEGRITY,
            ],
            'url discovery' => [
                'taskType' => Type::TYPE_URL_DISCOVERY,
            ],
        ];
    }
}
