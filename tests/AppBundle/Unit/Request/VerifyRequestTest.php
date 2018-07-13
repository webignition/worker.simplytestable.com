<?php

namespace Tests\AppBundle\Unit\Request;

use SimplyTestable\AppBundle\Request\VerifyRequest;

class VerifyRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $hostname
     * @param string $token
     * @param bool $expectedIsValid
     * @param string $expectedHostname
     * @param string $expectedToken
     */
    public function testCreate(
        $hostname,
        $token,
        $expectedIsValid,
        $expectedHostname,
        $expectedToken
    ) {
        $verifyRequest = new VerifyRequest($hostname, $token);

        $this->assertEquals($expectedHostname, $verifyRequest->getHostname());
        $this->assertEquals($expectedToken, $verifyRequest->getToken());
        $this->assertEquals($expectedIsValid, $verifyRequest->isValid());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty request' => [
                'hostname' => null,
                'token' => null,
                'expectedIsValid' => false,
                'expectedHostname' => null,
                'expectedToken' => null,
            ],
            'hostname empty' => [
                'hostname' => null,
                'token' => 'foo',
                'expectedIsValid' => false,
                'expectedHostname' => null,
                'expectedToken' => 'foo',
            ],
            'token empty' => [
                'hostname' => 'foo',
                'token' => null,
                'expectedIsValid' => false,
                'expectedHostname' => 'foo',
                'expectedToken' => null,
            ],
            'valid' => [
                'hostname' => 'foo',
                'token' => 'bar',
                'expectedIsValid' => true,
                'expectedHostname' => 'foo',
                'expectedToken' => 'bar',
            ],
        ];
    }
}
