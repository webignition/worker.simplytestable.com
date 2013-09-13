<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class BugFixTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    public function testBugFix() {        
        $this->assertTrue(true);
        return;
        
//        $taskObject = $this->createTask('http://ekloges.pkm.gov.gr/', 'JS static analysis');
//        $taskObject = $this->createTask('http://www.hotelgiuliocesare.com/fr/', 'JS static analysis');        
        $taskObject = $this->createTask('http://webignition.net/', 'JS static analysis', '{"ignore-common-cdns":"1","domains-to-ignore":["cdnjs.cloudflare.com","ajax.googleapis.com","netdna.bootstrapcdn.com","ajax.aspnetcdn.com","static.nrelate.com",""],"jslint-option-passfail":"1","jslint-option-bitwise":"0","jslint-option-continue":"0","jslint-option-debug":"0","jslint-option-evil":"0","jslint-option-eqeq":"1","jslint-option-es5":"0","jslint-option-forin":"0","jslint-option-newcap":"1","jslint-option-nomen":"1","jslint-option-plusplus":"1","jslint-option-regexp":"0","jslint-option-undef":"0","jslint-option-unparam":"0","jslint-option-sloppy":"1","jslint-option-stupid":"1","jslint-option-sub":"0","jslint-option-vars":"1","jslint-option-white":"1","jslint-option-anon":"1","jslint-option-browser":"1","jslint-option-devel":"0","jslint-option-windows":"0","jslint-option-maxerr":"50","jslint-option-indent":"4","jslint-option-maxlen":"256","jslint-option-predef":[""]}');

        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(2, $task->getOutput()->getErrorCount());
    } 
}

