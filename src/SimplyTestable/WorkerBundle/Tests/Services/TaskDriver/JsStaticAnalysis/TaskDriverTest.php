<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;

abstract class TaskDriverTest extends BaseTest {
    
    public function setUp() {
        parent::setUp();

        $this->container->get('simplytestable.services.nodeJslintWrapperService')->enableDeferToParentIfNoRawOutput();
    }

    protected function getTaskTypeName() {
        return 'JS Static Analysis';
    }

}
