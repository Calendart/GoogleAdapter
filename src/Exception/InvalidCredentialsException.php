<?php

namespace CalendArt\Adapter\Google\Exception;

use CalendArt\Exception\InvalidCredentialsInterface;

class InvalidCredentialsException extends ApiErrorException implements InvalidCredentialsInterface
{
}
