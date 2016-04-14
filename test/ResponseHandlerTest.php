<?php

namespace CalendArt\Adapter\Google;

use GuzzleHttp\Message\ResponseInterface;

class ResponseHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $response;
    private $api;

    protected function setUp()
    {
        $this->response = $this->prophesize('GuzzleHttp\Message\ResponseInterface');
        $this->api = new Api;
    }

    public function testHandleErrorsWithSuccessfulResponse()
    {
        $this->response->getStatusCode()->shouldBeCalled()->willReturn(200);
        $this->api->get($this->response->reveal());

        $this->response->getStatusCode()->shouldBeCalled()->willReturn(301);
        $this->api->get($this->response->reveal());
    }

    /**
     * @dataProvider getResponses
     */
    public function testHandleErrors($statusCode, $reasonPhrase, $exception)
    {
        $this->setExpectedException($exception);

        $this->response->getStatusCode()->shouldBeCalled()->willReturn($statusCode);
        $this->response->json()->shouldBeCalled()->willReturn(['error' => ['message' => 'foo']]);
        $this->response->getReasonPhrase()->willReturn($reasonPhrase);
        $this->api->get($this->response->reveal());
    }

    public function getResponses()
    {
        return [
            [400, 'Bad Request', 'CalendArt\Adapter\Google\Exception\BadRequestException'],
            [401, 'Invalid credentials', 'CalendArt\Adapter\Google\Exception\InvalidCredentialsException'],
            [403, 'Daily Limit Exceeded', 'CalendArt\Adapter\Google\Exception\DailyLimitExceededException'],
            [403, 'User Rate Limit Exceeded', 'CalendArt\Adapter\Google\Exception\UserRateLimitExceededException'],
            [403, 'Rate Limit Exceeded', 'CalendArt\Adapter\Google\Exception\RateLimitExceededException'],
            [403, 'Calendar usage limits exceeded.', 'CalendArt\Adapter\Google\Exception\CalendarUsageLimitsExceededException'],
            [404, 'Not Found', 'CalendArt\Adapter\Google\Exception\NotFoundException'],
            [409, 'The requested identifier already exists', 'CalendArt\Adapter\Google\Exception\IdentifierAlreadyExistsException'],
            [410, 'Gone', 'CalendArt\Adapter\Google\Exception\GoneException'],
            [412, 'Precondition Failed', 'CalendArt\Adapter\Google\Exception\PreconditionException'],
            [500, 'Backend Error', 'CalendArt\Adapter\Google\Exception\BackendException'],
        ];
    }
}

class Api
{
    use ResponseHandler;

    /**
     * Simulate a get method of an API
     */
    public function get(ResponseInterface $response)
    {
        $this->handleResponse($response);
    }
}
