<?php
declare(strict_types=1);

namespace App\Interface;

use App\Dto\Request\Geocoding\GeocodingRequestDto;
use App\Dto\Request\ReverseGeocoding\ReverseGeocodingRequestDto;
use App\Type\PostalAddress;
use App\Type\ValueObject\Coordinates;

interface GeocodingClientInterface {
    public function geocode(GeocodingRequestDto $dto): Coordinates;
    public function reverseGeocode(ReverseGeocodingRequestDto $dto): PostalAddress;
}