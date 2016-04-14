<?php

namespace CalendArt\Adapter\Google\Exception;

use CalendArt\Exception\NotFoundInterface;

class NotFoundException extends ApiErrorException implements NotFoundInterface
{
}
