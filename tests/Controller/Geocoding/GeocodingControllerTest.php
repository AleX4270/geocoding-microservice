<?php

namespace App\Tests\Controller\Geocoding;

use App\Dto\Address\CreateAddressDto;
use App\Enum\HttpStatus;
use App\Exception\Geocoding\CoordinatesNotFoundException;
use App\Interface\GeocodingClientInterface;
use App\Repository\AddressRepository;
use App\Service\Address\AddressService;
use App\Type\ValueObject\Coordinates;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeocodingControllerTest extends WebTestCase
{
    private $container;
    private $client;

    protected function setUp(): void {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public static function invalidParamsProvider(): iterable
    {
        yield 'missing street' => [['city' => 'Gniezno', 'province' => 'wielkopolskie', 'countrySymbol' => 'PL']];
        yield 'missing city' => [['street' => 'Krótka 1', 'province' => 'wielkopolskie', 'countrySymbol' => 'PL']];
        yield 'blank province' => [['street' => 'Krótka 1', 'city' => 'Gniezno', 'province' => '', 'countrySymbol' => 'PL']];
    }

    public function testReturnsCoordinatesFromGeocodingClient(): void
    {
        $params = [
            'street' => 'Krótka 1',
            'city' => 'Gniezno',
            'province' => 'Mazowieckie',
            'countrySymbol' => 'PL',
            'postalCode' => '75-231',
        ];

        $responseCoordinates = new Coordinates(52.4292009, 17.4884394);

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::once())
            ->method('geocode')
            ->willReturn($responseCoordinates);

        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/geocoding', $params);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            [
                'latitude' => $responseCoordinates->latitude,
                'longitude' => $responseCoordinates->longitude,
            ],
            $response['data'] ?? [],
        );
        $this->assertSame(1, $this->container->get(AddressRepository::class)->count());
    }

    public function testReturnsMatchingStoredCoordinates(): void
    {
        $params = [
            'street' => 'Krótka 1',
            'city' => 'Gniezno',
            'province' => 'Mazowieckie',
            'countrySymbol' => 'PL',
            'postalCode' => '75-231',
        ];

        $coordinates = new Coordinates(52.4292009, 17.4884394);

        $addressService = $this->container->get(AddressService::class);
        $addressService->create(new CreateAddressDto(
            address: $params['street'],
            city: $params['city'],
            province: $params['province'],
            countrySymbol: $params['countrySymbol'],
            postalCode: $params['postalCode'],
            coordinates: $coordinates,
        ));

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::never())->method('geocode');
        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/geocoding', $params);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            [
                'latitude' => $coordinates->latitude,
                'longitude' => $coordinates->longitude,
            ],
            $response['data'] ?? [],
        );
    }

    public function testReturnsCorrectDataStructure(): void
    {
        $params = [
            'street' => 'Krótka 1',
            'city' => 'Gniezno',
            'province' => 'Mazowieckie',
            'countrySymbol' => 'PL',
            'postalCode' => '75-231',
        ];

        $responseCoordinates = new Coordinates(52.4292009, 17.4884394);

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::once())
            ->method('geocode')
            ->willReturn($responseCoordinates);

        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/geocoding', $params);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('latitude', $response['data']);
        $this->assertArrayHasKey('longitude', $response['data']);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('timestamp', $response);
    }

    public function testReturnsNotFoundWhenUnableToFindCoordinates(): void
    {
        $params = [
            'street' => 'Krótka 1',
            'city' => 'Gniezno',
            'province' => 'Mazowieckie',
            'countrySymbol' => 'PL',
            'postalCode' => '75-231',
        ];

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::once())
            ->method('geocode')
            ->willThrowException(new CoordinatesNotFoundException());

        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/geocoding', $params);

        $this->assertResponseStatusCodeSame(HttpStatus::NOT_FOUND->value);
        $this->assertSame(0, $this->container->get(AddressRepository::class)->count());
    }

    #[DataProvider('invalidParamsProvider')]
    public function testReturnsUnprocessableForInvalidParams(array $params): void
    {
        $this->client->request('GET', '/geocoding', $params);
        $this->assertResponseIsUnprocessable();
    }
}
