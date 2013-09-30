<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

abstract class ConsoleCommandBaseTestCase extends BaseSimplyTestableTestCase {
    
    const CONSOLE_COMMAND_SUCCESS = 0;
    const CONSOLE_COMMAND_FAILURE = 1;
    
    
    protected function setHttpFixtures($fixtures) {        
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        
        foreach ($fixtures as $fixture) {
            if ($fixture instanceof \Exception) {
                $plugin->addException($fixture);
            } else {
                $plugin->addResponse($fixture);
            }
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
    protected function buildHttpFixtureSet($items) {
        $fixtures = array();
        
        foreach ($items as $item) {
            switch ($this->getHttpFixtureItemType($item)) {
                case 'httpMessage':
                    $fixtures[] = \Guzzle\Http\Message\Response::fromMessage($item);
                    break;
                
                case 'curlException':
                    $fixtures[] = $this->getCurlExceptionFromCurlMessage($item);                    
                    break;
                
                default:
                    throw new \LogicException();
            }
        }
        
        return $fixtures;
    }
    
    private function getHttpFixtureItemType($item) {
        if (substr($item, 0, strlen('HTTP')) == 'HTTP') {
            return 'httpMessage';
        }
        
        return 'curlException';
    }
    
    
    /**
     * 
     * @param string $curlMessage
     * @return \Guzzle\Http\Exception\CurlException
     */
    private function getCurlExceptionFromCurlMessage($curlMessage) {
        $curlMessageParts = explode(' ', $curlMessage, 2);
        
        $curlException = new \Guzzle\Http\Exception\CurlException();
        $curlException->setError($curlMessageParts[1], (int)  str_replace('CURL/', '', $curlMessageParts[0]));
        
        return $curlException;
    }

}
