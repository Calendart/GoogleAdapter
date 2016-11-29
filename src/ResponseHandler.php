<?php

namespace CalendArt\Adapter\Google;

use Psr\Http\Message\ResponseInterface;

trait ResponseHandler
{
    /**
     * @param ResponseInterface $response
     *
     * @throws Exception\BadRequestException
     * @throws Exception\InvalidCredentialsException
     * @throws Exception\DailyLimitExceededException
     * @throws Exception\UserRateLimitExceededException
     * @throws Exception\RateLimitExceededException
     * @throws Exception\CalendarUsageLimitsExceededException
     * @throws Exception\NotFoundException
     * @throws Exception\IdentifierAlreadyExistsException
     * @throws Exception\GoneException
     * @throws Exception\PreconditionException
     * @throws Exception\BackendException
     */
    private function handleResponse(ResponseInterface $response)
    {
        $statusCode = (int) $response->getStatusCode();

        switch (true) {
            case $statusCode >= 200 && $statusCode < 400:
                return;

            case 400 === $statusCode:
                throw new Exception\BadRequestException($response);

            case 401 === $statusCode:
                throw new Exception\InvalidCredentialsException($response);

            case 403 === $statusCode && 'Daily Limit Exceeded' === $response->getReasonPhrase():
                throw new Exception\DailyLimitExceededException($response);

            case 403 === $statusCode && 'User Rate Limit Exceeded' === $response->getReasonPhrase():
                throw new Exception\UserRateLimitExceededException($response);

            case 403 === $statusCode && 'Rate Limit Exceeded' === $response->getReasonPhrase():
                throw new Exception\RateLimitExceededException($response);

            case 403 === $statusCode && 'Calendar usage limits exceeded.' === $response->getReasonPhrase():
                throw new Exception\CalendarUsageLimitsExceededException($response);

            case 404 === $statusCode:
                throw new Exception\NotFoundException($response);

            case 409 === $statusCode:
                throw new Exception\IdentifierAlreadyExistsException($response);

            case 410 === $statusCode:
                throw new Exception\GoneException($response);

            case 412 === $statusCode:
                throw new Exception\PreconditionException($response);

            case 500 === $statusCode:
            default:
                throw new Exception\BackendException($response);
        }
    }
}
