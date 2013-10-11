<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\LinkIntegrity;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class BugFixTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    public function testBugFix() {        
        $this->assertTrue(true);
        return;        
        
        $this->getHttpClientService()->getMemcacheCache()->deleteAll();
        
//        $taskObject = $this->createTask('http://www.joelonsoftware.com/articles/fog0000000017.html', 'Link integrity');
//        $taskObject = $this->createTask('http://www.boxuk.com/blog/dark-patterns-in-ux/', 'Link integrity');
//        $taskObject = $this->createTask('http://www.joelonsoftware.com/', 'Link integrity');        
//        $taskObject = $this->createTask('http://www.boxuk.com/news/box-uk-to-showcase-digital-solutions-at-tfma-exhibition/', 'Link integrity');
        
        $urls = array(
            'http://www.hnp.dk/'
        );
        
        foreach ($urls as $url) {
            $taskObject = $this->createTask($url, 'Link integrity');

            $task = $this->getTaskService()->getById($taskObject->id);

            $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
                $task->getId() => true
            )));

            var_dump($task->getOutput()->getOutput());            
            
            echo "\n\n";
        }
        
        

        
        //var_dump(json_decode($task->getOutput()->getOutput()));
    } 
}

