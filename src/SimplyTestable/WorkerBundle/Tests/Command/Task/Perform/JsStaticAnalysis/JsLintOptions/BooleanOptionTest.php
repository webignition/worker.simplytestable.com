<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class BooleanOptionTest extends ConsoleCommandBaseTestCase {
    
    const NON_FILTERED_ERROR_COUNT = 15;
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    } 
    
    /**
     *
     * @var array
     */
    protected $jsLintBooleanOptions = array(
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
    public function testOneOff() {               
        $this->offTest(1);
    } 
    
    /**
     * @group standard
     */    
    public function testOneOn() {               
        $this->onTest(1);
    } 
    
    

    protected function offTest($optionCount) {                
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $optionGroups = $this->getOptionGroups($optionCount);
        
        foreach ($optionGroups as $optionGroup) {
            $optionsValues = array();
            foreach ($optionGroup as $name) {
                $optionsValues[$name] = "0";
            }
            
            $taskObject = $this->createTask('http://example.com/', 'JS static analysis', json_encode($optionsValues));

            $task = $this->getTaskService()->getById($taskObject->id);

            $response = $this->runConsole('simplytestable:task:perform', array(
                $task->getId() => true
            ));

            $this->assertEquals(0, $response);
            $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());              
        }
    } 
    
    protected function onTest($optionCount) {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $optionGroups = $this->getOptionGroups($optionCount);
        
        foreach ($optionGroups as $optionGroup) {
            $optionsValues = array();
            foreach ($optionGroup as $name) {
                $optionsValues[$name] = "1";
            }
            
            $taskObject = $this->createTask('http://example.com/', 'JS static analysis', json_encode($optionsValues));

            $task = $this->getTaskService()->getById($taskObject->id);

            $response = $this->runConsole('simplytestable:task:perform', array(
                $task->getId() => true
            ));

            $this->assertEquals(0, $response);
            $this->assertEquals(self::NON_FILTERED_ERROR_COUNT - 1, $task->getOutput()->getErrorCount());              
        }
    }
    
    private function getOptionGroups($optionCount) {
        $optionGroups = array();
        if ($optionCount === 1) {
            foreach ($this->jsLintBooleanOptions as $booleanOption) {
                $optionGroups[] = array('jslint-option-' . $booleanOption);
            }
        }
        
        return $optionGroups;
    }
    
    protected function getFixturesDataPath() {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}
