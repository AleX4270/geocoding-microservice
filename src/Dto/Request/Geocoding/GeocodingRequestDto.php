<?php

declare(strict_types=1);

namespace App\Dto\Request\Geocoding;

use Symfony\Component\Validator\Constraints;

final class GeocodingRequestDto
{
    #[Constraints\NotBlank]
    public string $street;

    #[Constraints\NotBlank]
    public string $city;

    #[Constraints\NotBlank]
    public string $province;

    #[Constraints\NotBlank]
    public string $countrySymbol;

    public ?string $postalCode = null;

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'province' => $this->province,
            'countrySymbol' => $this->countrySymbol,
            'postalCode' => $this->postalCode,
        ];
    }
}
