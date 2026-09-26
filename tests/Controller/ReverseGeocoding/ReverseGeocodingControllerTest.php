<?php

namespace App\Tests\Controller\ReverseGeocoding;

use App\Dto\Address\CreateAddressDto;
use App\Enum\HttpStatus;
use App\Exception\ReverseGeocoding\AddressNotFoundException;
use App\Interface\GeocodingClientInterface;
use App\Repository\AddressRepository;
use App\Service\Address\AddressService;
use App\Type\PostalAddress;
use App\Type\ValueObject\Coordinates;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReverseGeocodingControllerTest extends WebTestCase
{
    private $container;
    private $client;

    protected function setUp(): void {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public static function invalidParamsProvider(): iterable
    {
        yield 'missing latitude' => [['longitude' => 17.4884394]];
        yield 'missing longitude' => [['latitude' => 52.4292009]];
        yield 'latitude below -90' => [['longitude' => 17.4884394, 'latitude' => -91.0]];
        yield 'latitude above 90' => [['longitude' => 17.4884394, 'latitude' => 91.0]];
        yield 'longitude below -180' => [['longitude' => -181.0, 'latitude' => 52.4292009]];
        yield 'longitude above 180' => [['longitude' => 181.0, 'latitude' => 52.4292009]];
    }

    public function testReturnsAddressFromGeocodingClient(): void
    {
        $params = [
            'latitude' => 52.4292009,
            'longitude' => 17.4884394,
        ];

        $responsePostalAddress = new PostalAddress(
            street: 'Krótka 1',
            city: 'Gniezno',
            province: 'Mazowieckie',
            countrySymbol: 'PL',
            postalCode: '75-231',
        );

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::once())
            ->method('reverseGeocode')
            ->willReturn($responsePostalAddress);

        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/reverse-geocoding', $params);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            [
                'street' => $responsePostalAddress->street,
                'city' => $responsePostalAddress->city,
                'province' => $responsePostalAddress->province,
                'countrySymbol' => $responsePostalAddress->countrySymbol,
                'postalCode' => $responsePostalAddress->postalCode,
            ],
            $response['data'] ?? [],
        );
        $this->assertSame(1, $this->container->get(AddressRepository::class)->count());
    }

    public function testReturnsMatchingStoredAddress(): void
    {
        $params = [
            'latitude' => 52.4292009,
            'longitude' => 17.4884394,
        ];

        $coordinates = new Coordinates(52.4292009, 17.4884394);

        $addressDto = new CreateAddressDto(
            address: 'Krótka 1',
            city: 'Gniezno',
            province: 'Mazowieckie',
            countrySymbol: 'PL',
            postalCode: '75-231',
            coordinates: $coordinates,
        );

        $addressService = $this->container->get(AddressService::class);
        $addressService->create($addressDto);

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::never())->method('reverseGeocode');
        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/reverse-geocoding', $params);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            [
                'street' => $addressDto->address,
                'city' => $addressDto->city,
                'province' => $addressDto->province,
                'countrySymbol' => $addressDto->countrySymbol,
                'postalCode' => $addressDto->postalCode,
            ],
            $response['data'] ?? [],
        );
    }

    public function testReturnsCorrectDataStructure(): void
    {
        $params = [
            'latitude' => 52.4292009,
            'longitude' => 17.4884394,
        ];

        $responsePostalAddress = new PostalAddress(
            street: 'Krótka 1',
            city: 'Gniezno',
            province: 'Mazowieckie',
            countrySymbol: 'PL',
            postalCode: '75-231',
        );

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::once())
            ->method('reverseGeocode')
            ->willReturn($responsePostalAddress);

        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/reverse-geocoding', $params);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('street', $response['data']);
        $this->assertArrayHasKey('city', $response['data']);
        $this->assertArrayHasKey('province', $response['data']);
        $this->assertArrayHasKey('countrySymbol', $response['data']);
        $this->assertArrayHasKey('postalCode', $response['data']);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('timestamp', $response);
    }

    public function testReturnsNotFoundWhenUnableToFindAddress(): void
    {
        $params = [
            'latitude' => 52.4292009,
            'longitude' => 17.4884394,
        ];

        $geocodingClient = $this->createMock(GeocodingClientInterface::class);
        $geocodingClient->expects(self::once())
            ->method('reverseGeocode')
            ->willThrowException(new AddressNotFoundException());

        $this->container->set(GeocodingClientInterface::class, $geocodingClient);

        $this->client->request('GET', '/reverse-geocoding', $params);

        $this->assertResponseStatusCodeSame(HttpStatus::NOT_FOUND->value);
        $this->assertSame(0, $this->container->get(AddressRepository::class)->count());
    }

    #[DataProvider('invalidParamsProvider')]
    public function testReturnsUnprocessableForInvalidParams(array $params): void
    {
        $this->client->request('GET', '/reverse-geocoding', $params);
        $this->assertResponseIsUnprocessable();
    }
}
