<?php

declare(strict_types=1);

namespace App\Dto\Request\ReverseGeocoding;

use Symfony\Component\Validator\Constraints;

final class ReverseGeocodingRequestDto
{
    #[Constraints\NotNull]
    #[Constraints\Range(min: -90, max: 90)]
    public float $latitude;

    #[Constraints\NotNull]
    #[Constraints\Range(min: -180, max: 180)]
    public float $longitude;

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
