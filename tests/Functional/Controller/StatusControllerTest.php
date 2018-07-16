<?php

namespace App\Tests\Functional\Controller;

class StatusControllerTest extends AbstractControllerTest
{
    public function testIndexAction()
    {
        $this->client->request('GET', $this->router->generate('status'));
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
