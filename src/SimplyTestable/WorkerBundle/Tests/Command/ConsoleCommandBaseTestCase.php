<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

abstract class ConsoleCommandBaseTestCase extends BaseSimplyTestableTestCase {
    
    const CONSOLE_COMMAND_SUCCESS = 0;
    const CONSOLE_COMMAND_FAILURE = 1;
    
    
    protected function setHttpFixtures($fixtures) {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        
        foreach ($fixtures as $fixture) {
            $plugin->addResponse($fixture);
        }
         
        $this->getHttpClientService()->get()->addSubscriber($plugin);              
    }
    
    
    protected function getHttpFixtures($path) {
        $httpMessages = array();

        $fixturesDirectory = new \DirectoryIterator($path);
        $fixturePaths = array();
        foreach ($fixturesDirectory as $directoryItem) {
            if ($directoryItem->isFile()) {                
                $fixturePaths[] = $directoryItem->getPathname();
            }
        }        
        
        sort($fixturePaths);
        
        foreach ($fixturePaths as $fixturePath) {
            $httpMessages[] = file_get_contents($fixturePath);
        }
        
        return $this->buildHttpFixtureSet($httpMessages);
    }
    
    
    /**
     * 
     * @param array $httpMessages
     * @return array
     */
    protected function buildHttpFixtureSet($httpMessages) {
        $fixtures = array();
        
        foreach ($httpMessages as $httpMessage) {
            $fixtures[] = \Guzzle\Http\Message\Response::fromMessage($httpMessage);
        }
        
        return $fixtures;
    }

}
