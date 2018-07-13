<?php

namespace Tests\WorkerBundle\Functional\Controller;

class StatusControllerTest extends AbstractControllerTest
{
    public function testIndexAction()
    {
        $this->client->request('GET', $this->router->generate('SimplyTestableWorkerBundle_status'));
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
