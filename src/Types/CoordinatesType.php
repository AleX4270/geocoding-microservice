<?php

declare(strict_types=1);

namespace App\Types;

use App\ValueObject\Coordinates;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Jsor\Doctrine\PostGIS\Types\GeographyType;

final class CoordinatesType extends GeographyType
{
    public function convertToPHPValue(mixed $value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform): ?Coordinates
    {
        if (null === $value) {
            return null;
        }

        $pattern = '/^(?:SRID=(?<srid>\d+);)?POINT\s*\(\s*'
            .'(?<lon>[-+]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][-+]?\d+)?)\s+'
            .'(?<lat>[-+]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][-+]?\d+)?)\s*\)$/';

        if (!is_string($value) || 1 !== preg_match($pattern, $value, $m)) {
            throw ValueNotConvertible::new($value, Coordinates::class);
        }

        return new Coordinates(
            latitude: (float) $m['lat'],
            longitude: (float) $m['lon'],
        );
    }

    public function convertToDatabaseValue(mixed $value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Coordinates) {
            throw InvalidType::new($value, self::GEOGRAPHY, [Coordinates::class]);
        }

        return sprintf('SRID=4326;POINT(%.8F %.8F)', $value->longitude, $value->latitude);
    }
}
