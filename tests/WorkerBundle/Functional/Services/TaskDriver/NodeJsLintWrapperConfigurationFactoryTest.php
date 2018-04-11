<?php

namespace Tests\WorkerBundle\Functional\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\NodeJsLintWrapperConfigurationFactory;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use webignition\NodeJslint\Wrapper\Configuration\Configuration as WrapperConfiguration;
use webignition\NodeJslint\Wrapper\Configuration\Flag\JsLint as JsLintFlag;
use webignition\NodeJslint\Wrapper\Configuration\Option\JsLint as JsLintOption;

class NodeJsLintWrapperConfigurationFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var NodeJsLintWrapperConfigurationFactory
     */
    private $configurationFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configurationFactory = $this->container->get(NodeJsLintWrapperConfigurationFactory::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array $taskParameters
     * @param array $expectedFlags
     * @param array $expectedOptions
     */
    public function testCreate(array $taskParameters, array $expectedFlags, array $expectedOptions)
    {
        $testTaskFactory = new TestTaskFactory($this->container);

        $task = $testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => TaskTypeService::CSS_VALIDATION_NAME,
                'parameters' => json_encode($taskParameters),
            ])
        );

        $configuration = $this->configurationFactory->create($task);

        $this->assertInstanceOf(WrapperConfiguration::class, $configuration);

        $this->assertEquals($expectedFlags, $configuration->getFlags());
        $this->assertEquals($expectedOptions, $configuration->getOptions());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $allFlags = JsLintFlag::getList();
        $allConfigurationFlagsEnabled = [];
        $allConfigurationFlagsDisabled = [];
        $allParameterFlagsEnabled = [];
        $allParameterFlagsDisabled = [];

        $allConfigurationOptionsSet = [
            JSLintOPtion::INDENT => 12,
            JSLintOPtion::MAXERR => 99,
            JSLintOPtion::MAXLEN => 15,
            JSLintOPtion::PREDEF => ['window'],
        ];

        $allParameterOptionsSet = [
            'jslint-option-' . JSLintOPtion::INDENT => 12,
            'jslint-option-' . JSLintOPtion::MAXERR => 99,
            'jslint-option-' . JSLintOPtion::MAXLEN => 15,
            'jslint-option-' . JSLintOPtion::PREDEF => 'window',
        ];

        foreach ($allFlags as $name) {
            $allConfigurationFlagsEnabled[$name] = true;
            $allConfigurationFlagsDisabled[$name] = false;
            $allParameterFlagsEnabled['jslint-option-' . $name] = true;
            $allParameterFlagsDisabled['jslint-option-' . $name] = false;
        }

        return [
            'no task parameters' => [
                'taskParameters' => [],
                'expectedFlags' => [],
                'expectedOptions' => [],
            ],
            'enable all boolean parameters' => [
                'taskParameters' => array_merge($allParameterFlagsEnabled, $allParameterOptionsSet),
                'expectedFlags' => $allConfigurationFlagsEnabled,
                'expectedOptions' => $allConfigurationOptionsSet,
            ],
        ];
    }
}
