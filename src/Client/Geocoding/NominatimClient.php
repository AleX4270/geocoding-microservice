<?php

declare(strict_types=1);

namespace App\Client\Geocoding;

use App\Dto\Request\Geocoding\GeocodingRequestDto;
use App\Dto\Request\ReverseGeocoding\ReverseGeocodingRequestDto;
use App\Enum\NominatimEndpoint;
use App\Exception\Geocoding\CoordinatesNotFoundException;
use App\Exception\ReverseGeocoding\AddressNotFoundException;
use App\Interface\GeocodingClientInterface;
use App\Type\PostalAddress;
use App\Type\ValueObject\Coordinates;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NominatimClient implements GeocodingClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $nominatimClient,
    ) {
    }

    public function geocode(GeocodingRequestDto $dto): Coordinates
    {
        $queryParams = [
            'street' => $dto->street,
            'city' => $dto->city,
            'country' => $dto->countrySymbol,
            'format' => 'json',
        ];

        if (!empty($dto->postalCode)) {
            $queryParams['postalcode'] = $dto->postalCode;
        }

        $response = $this->nominatimClient->request('GET', NominatimEndpoint::GEOCODE->value, [
            'query' => $queryParams,
        ]);

        $data = $response->toArray();

        if (empty($data) || empty($data[0])) {
            throw new CoordinatesNotFoundException();
        }

        return new Coordinates(
            latitude: (float) $data[0]['lat'],
            longitude: (float) $data[0]['lon'],
        );
    }

    public function reverseGeocode(ReverseGeocodingRequestDto $dto): PostalAddress
    {
        $queryParams = [
            'lat' => $dto->latitude,
            'lon' => $dto->longitude,
            'format' => 'json',
            'accept-language' => 'pl',
        ];

        $response = $this->nominatimClient->request('GET', NominatimEndpoint::REVERSE_GEOCODE->value, [
            'query' => $queryParams,
        ]);

        $data = $response->toArray();

        if (empty($data) || empty($data['address'])) {
            throw new AddressNotFoundException();
        }

        $address = $data['address'];

        return new PostalAddress(
            street: trim(($address['road'] ?? '').' '.($address['house_number'] ?? '')),
            city: $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? '',
            province: preg_replace('/^województwo\s+/iu', '', $address['state'] ?? $address['region'] ?? $address['county'] ?? ''),
            countrySymbol: strtoupper($address['country_code'] ?? ''),
            postalCode: $address['postcode'] ?? '',
        );
    }
}
