<?php

declare(strict_types=1);

namespace App\Service\Geocoding;

use App\Dto\Request\Geocoding\GeocodingRequestDto;
use App\Entity\Country;
use App\ValueObject\Coordinates;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeocodingService
{
    public function __construct(
        #[Autowire(env: 'NOMINATIM_API_URL')]
        private readonly string $nominatimApiUrl,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function geocode(GeocodingRequestDto $dto): Coordinates
    {
        // TODO: Check if the result is already in the database
        // TODO: Move the nominatim geocoding to the external service

        $baseUrl = $this->nominatimApiUrl;
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
            throw new \Exception('Error no data!');
            // TODO: Implement custom errors with error handling
            // throw new CoordinatesNotFoundException('Geocoding result data empty');
        }

        // TODO: Save result to the database

        return new Coordinates(
            latitude: (float) $data[0]['lat'],
            longitude: (float) $data[0]['lon'],
        );
    }
}
