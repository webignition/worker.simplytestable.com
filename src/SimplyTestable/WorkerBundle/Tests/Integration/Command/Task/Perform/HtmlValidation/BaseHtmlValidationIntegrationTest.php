<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

abstract class BaseHtmlValidationIntegrationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    public function setUp() {
        parent::setUp();
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->enableDeferToParentIfNoRawOutput();
    } 
}

