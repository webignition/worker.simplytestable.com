<?php

namespace App\Tests\Unit\Request;

use App\Services\Request\Factory\VerifyRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class VerifyRequestFactoryTest extends \PHPUnit\Framework\TestCase
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
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $createRequestFactory = new VerifyRequestFactory($requestStack);
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
