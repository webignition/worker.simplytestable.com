<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class SingleBooleanOptionTest extends ConsoleCommandBaseTestCase {
    
    const NON_FILTERED_ERROR_COUNT = 15;
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    } 
    
    /**
     *
     * @var array
     */
    private $jsLintBooleanOptions = array(
        'bitwise',
        'continue',
        'debug',
        'evil',
        'eqeq',
        'es5',
        'forin',
        'newcap',
        'nomen',
        'plusplus',
        'regexp',
        'undef',
        'unparam',
        'sloppy',
        'stupid',
        'sub',
        'vars',
        'white',
        'anon',
        'browser',
        'devel',
        'windows',       
    );   
    
    
    /**
     * @group standard
     */    
    public function testOff() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        foreach ($this->jsLintBooleanOptions as $booleanOption) {
            $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-'.$booleanOption.'":"0"}');

            $task = $this->getTaskService()->getById($taskObject->id);

            $response = $this->runConsole('simplytestable:task:perform', array(
                $task->getId() => true
            ));

            $this->assertEquals(0, $response);
            $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());            
        }
    } 
    
    /**
     * @group standard
     */    
    public function testOn() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        foreach ($this->jsLintBooleanOptions as $booleanOption) {
            $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-'.$booleanOption.'":"1"}');

            $task = $this->getTaskService()->getById($taskObject->id);

            $response = $this->runConsole('simplytestable:task:perform', array(
                $task->getId() => true
            ));

            $this->assertEquals(0, $response);
            $this->assertEquals(self::NON_FILTERED_ERROR_COUNT - 1, $task->getOutput()->getErrorCount());            
        }
    }    
    
    protected function getFixturesDataPath() {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}
