<?php

namespace App\Tests\Functional\Controller;

class StatusControllerTest extends AbstractControllerTest
{
    public function testIndexAction()
    {
        $this->client->request('GET', $this->router->generate('status'));
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(self::$container->getParameter('hostname'), $responseData['hostname']);
    }
}
