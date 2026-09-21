<?php

declare(strict_types=1);

namespace App\Enum;

enum NominatimEndpoint: string
{
    case GEOCODE = '/search';
    case REVERSE_GEOCODE = '/reverse';
}
