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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NominatimClient implements GeocodingClientInterface {
    public function __construct(
        #[Autowire(env: 'NOMINATIM_API_URL')]
        private readonly string $nominatimApiUrl,
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function geocode(GeocodingRequestDto $dto): Coordinates {
        $baseUrl = $this->nominatimApiUrl . NominatimEndpoint::GEOCODE->value;
        $queryParams = [
            'street' => $dto->street,
            'city' => $dto->city,
            'country' => $dto->countrySymbol,
            'format' => 'json',
        ];

        if (!empty($dto->postalCode)) {
            $queryParams['postalcode'] = $dto->postalCode;
        }

        $response = $this->httpClient->request('GET', $baseUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
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

    public function reverseGeocode(ReverseGeocodingRequestDto $dto): PostalAddress {
        $baseUrl = $this->nominatimApiUrl . NominatimEndpoint::REVERSE_GEOCODE->value;
        $queryParams = [
            'lat' => $dto->latitude,
            'lon' => $dto->longitude,
            'format' => 'json',
            'accept-language' => 'pl',
        ];

        $response = $this->httpClient->request('GET', $baseUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'query' => $queryParams,
        ]);

        $data = $response->toArray();

        if (empty($data) || empty($data['address'])) {
            throw new AddressNotFoundException();
        }

        $address = $data['address'];
        return new PostalAddress(
            street: trim(($address['road'] ?? '') . ' ' . ($address['house_number'] ?? '')),
            city: $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? '',
            province: preg_replace('/^województwo\s+/iu', '', $address['state'] ?? $address['region'] ?? $address['county'] ?? ''),
            countrySymbol: strtoupper($address['country_code'] ?? ''),
            postalCode: $address['postcode'] ?? '',
        );
    }
}