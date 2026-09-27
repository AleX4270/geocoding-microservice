<?php

declare(strict_types=1);

namespace App\Dto\Address;

final readonly class FindAddressDto
{
    public function __construct(
        public string $address,
        public string $city,
        public string $countrySymbol,
        public ?string $postalCode = null,
    ) {
    }
}
