<?php

declare(strict_types=1);

namespace App\Exception\ReverseGeocoding;

use App\Enum\HttpStatus;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Attribute\WithLogLevel;

#[WithHttpStatus(HttpStatus::NOT_FOUND->value)]
#[WithLogLevel(LogLevel::WARNING)]
final class AddressNotFoundException extends \RuntimeException
{
}
