<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity;

class HttpAuthParametersTest extends TaskDriverTest {

    const USERNAME = 'example';
    const PASSWORD = 'password';
    protected function getTaskParameters() {
        return [
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        ];
    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    public function testUsernameAndPasswordAreSetOnAllRequestSent() {
        foreach ($this->getAllRequests() as $request) {
            $this->assertEquals(self::USERNAME, $request->getUsername());
            $this->assertEquals(self::PASSWORD, $request->getPassword());
        }
    }

    /**
     * @return \Guzzle\Http\Message\Request[]
     */
    private function getAllRequests() {
        $requests = array();

        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            $requests[] = $httpTransaction['request'];
        }

        return $requests;
    }
    
}
