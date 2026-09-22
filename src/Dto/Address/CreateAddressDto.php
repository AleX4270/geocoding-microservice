<?php

declare(strict_types=1);

namespace App\Dto\Address;

use App\Type\ValueObject\Coordinates;
use Symfony\Component\Validator\Constraints;

final readonly class CreateAddressDto
{
    public function __construct(
        #[Constraints\NotBlank]
        public string $address,
        #[Constraints\NotBlank]
        public string $city,
        #[Constraints\NotBlank]
        public string $province,
        #[Constraints\NotBlank]
        public string $countrySymbol,
        public Coordinates $coordinates,
        public ?string $postalCode = null,
    ) {
    }
}
