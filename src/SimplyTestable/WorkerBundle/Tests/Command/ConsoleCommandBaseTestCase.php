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
        $fixtures = array();
        
        $fixturesDirectory = new \DirectoryIterator($path);
        foreach ($fixturesDirectory as $directoryItem) {
            if ($directoryItem->isFile()) {                
                $fixtures[] = \Guzzle\Http\Message\Response::fromMessage(file_get_contents($directoryItem->getPathname()));
            }
        }
        
        return $fixtures;
    }     

}
