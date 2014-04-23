<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\HandleMalformedContentType;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\StandardCssValidationTaskDriverTest;

abstract class HandleMalformedContentTypeTest extends StandardCssValidationTaskDriverTest {    
    
//    public function testM() {
//        //$task = $this->getDefaultTask();
//        
//        $url = 'http://yoinfluyo.com/index.php?Itemid=181&id=37&layout=blog&option=com_content&view=section';
//        
//        $task = $this->getTask($url);
//        
//        $this->getTaskService()->perform($task);
//    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    protected function getExpectedWarningCount() {
        return 0;
    }

    protected function getFixtureTestName() {
        return null;
        
        return $this->getName();
    }

    protected function getFixtureUpLevelsCount() {
        return null;
    }

    protected function getTaskParameters() {
        return null;
    }

}
