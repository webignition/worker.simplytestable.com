<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Request;

use SimplyTestable\WorkerBundle\Services\Request\Factory\VerifyRequestFactory;
use Symfony\Component\HttpFoundation\Request;

class VerifyRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param string $expectedHostname
     * @param string $expectedToken
     */
    public function testCreate(Request $request, $expectedHostname, $expectedToken)
    {
        $createRequestFactory = new VerifyRequestFactory($request);
        $verifyRequest = $createRequestFactory->create();

        $this->assertEquals($expectedHostname, $verifyRequest->getHostname());
        $this->assertEquals($expectedToken, $verifyRequest->getToken());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty' => [
                'request' => new Request(),
                'expectedHostname' => '',
                'expectedToken' => '',
            ],
            'non-empty' => [
                'request' => new Request([], [
                    'hostname' => 'foo',
                    'token' => 'bar',
                ]),
                'expectedHostname' => 'foo',
                'expectedToken' => 'bar',
            ],
        ];
    }
}
