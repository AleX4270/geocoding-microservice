<?php

declare(strict_types=1);

namespace App\Type\ValueObject;

final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }

    // TODO: Add validation and equals
}
