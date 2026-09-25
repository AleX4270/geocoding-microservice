<?php

declare(strict_types=1);

namespace App\Dto\Address;

use App\Type\ValueObject\Coordinates;
use Symfony\Component\Validator\Constraints;

final readonly class CreateAddressDto
{
    #[Constraints\NotBlank]
    public string $province;

    public function __construct(
        #[Constraints\NotBlank]
        public string $address,
        #[Constraints\NotBlank]
        public string $city,
        #[Constraints\NotBlank]
        string $province,
        #[Constraints\NotBlank]
        public string $countrySymbol,
        public Coordinates $coordinates,
        public ?string $postalCode = null,
    ) {
        $this->province = mb_strtolower($province);
    }
}
