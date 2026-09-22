<?php

declare(strict_types=1);

namespace App\Type;

final readonly class PostalAddress
{
    public function __construct(
        public ?string $street,
        public ?string $city,
        public ?string $province,
        public ?string $countrySymbol,
        public ?string $postalCode,
    ) {
    }
}
