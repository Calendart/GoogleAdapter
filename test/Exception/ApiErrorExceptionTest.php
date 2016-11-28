<?php

namespace CalendArt\Adapter\Google;

use Psr\Http\Message\ResponseInterface;

use CalendArt\Adapter\Google\Exception\BadRequestException;

class ApiErrorExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructWithUnexceptedFormat()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->shouldBeCalled()->willReturn('bad format');
        $response->getStatusCode()->shouldBeCalled()->willReturn(400);
        $response->getReasonPhrase()->shouldBeCalled()->willReturn('Invalid Argument');

        $e = new BadRequestException($response->reveal());

        $this->assertEquals('The request failed and returned an invalid status code ("400") : Invalid Argument', $e->getMessage());
    }

    public function testConstructWithExceptedFormat()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->shouldBeCalled()->willReturn(json_encode(['error' => ['message' => 'Api Message']]));
        $response->getStatusCode()->shouldBeCalled()->willReturn(400);
        $response->getReasonPhrase()->shouldNotBeCalled();

        $e = new BadRequestException($response->reveal());

        $this->assertEquals('The request failed and returned an invalid status code ("400") : Api Message', $e->getMessage());
    }
}
