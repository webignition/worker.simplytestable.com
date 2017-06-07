<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

abstract class JsLintOptionsTest extends TaskDriverTest {

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));

        $this->setJsLintValidatorFixture(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/NodeJslintResponse/1'))
        );
    }
}
