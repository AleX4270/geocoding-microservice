<?php

namespace App\Tests\Client\Geocoding;

use App\Client\Geocoding\NominatimClient;
use App\Dto\Request\Geocoding\GeocodingRequestDto;
use App\Dto\Request\ReverseGeocoding\ReverseGeocodingRequestDto;
use App\Exception\Geocoding\CoordinatesNotFoundException;
use App\Exception\ReverseGeocoding\AddressNotFoundException;
use App\Type\PostalAddress;
use App\Type\ValueObject\Coordinates;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class NominatimClientTest extends TestCase
{
    private const BASE_URL = 'https://nominatim.test';

    public static function cityFallbackProvider(): iterable
    {
        yield 'town' => [['town' => 'Czerniejewo'], 'Czerniejewo'];
        yield 'village' => [['village' => 'Braciszewo'], 'Braciszewo'];
        yield 'municipality' => [['municipality' => 'Gmina Gniezno'], 'Gmina Gniezno'];
        yield 'city wins over town' => [['city' => 'Gniezno', 'town' => 'Czerniejewo'], 'Gniezno'];
    }

    public function testGeocodeReturnsCoordinatesFromFirstResult(): void
    {
        $response = new JsonMockResponse([
            ['lat' => '52.5361', 'lon' => '17.5934'],
            ['lat' => '50.0000', 'lon' => '19.0000'],
        ]);

        $coordinates = $this->createClient($response)->geocode($this->geocodingDto());

        $this->assertEquals(new Coordinates(52.5361, 17.5934), $coordinates);
    }

    public function testGeocodeSendsAddressQueryToSearchEndpoint(): void
    {
        $response = new JsonMockResponse([['lat' => '52.5361', 'lon' => '17.5934']]);

        $this->createClient($response)->geocode($this->geocodingDto());

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertStringStartsWith(self::BASE_URL.'/search?', $response->getRequestUrl());
        $this->assertSame(
            [
                'street' => 'Krótka 1',
                'city' => 'Gniezno',
                'country' => 'PL',
                'format' => 'json',
                'postalcode' => '62-200',
            ],
            $this->requestQuery($response),
        );
    }

    #[TestWith([null])]
    #[TestWith([''])]
    public function testGeocodeOmitsPostalCodeWhenEmpty(?string $postalCode): void
    {
        $response = new JsonMockResponse([['lat' => '52.5361', 'lon' => '17.5934']]);

        $this->createClient($response)->geocode($this->geocodingDto(postalCode: $postalCode));

        $this->assertArrayNotHasKey('postalcode', $this->requestQuery($response));
    }

    public function testGeocodeThrowsWhenNothingFound(): void
    {
        $this->expectException(CoordinatesNotFoundException::class);

        $this->createClient(new JsonMockResponse([]))->geocode($this->geocodingDto());
    }

    public function testReverseGeocodeMapsAddress(): void
    {
        $response = new JsonMockResponse([
            'address' => [
                'road' => 'Krótka',
                'house_number' => '1',
                'city' => 'Gniezno',
                'state' => 'województwo wielkopolskie',
                'postcode' => '62-200',
                'country_code' => 'pl',
            ],
        ]);

        $address = $this->createClient($response)->reverseGeocode($this->reverseGeocodingDto());

        $this->assertEquals(
            new PostalAddress(
                street: 'Krótka 1',
                city: 'Gniezno',
                province: 'wielkopolskie',
                countrySymbol: 'PL',
                postalCode: '62-200',
            ),
            $address,
        );
    }

    public function testReverseGeocodeSendsCoordinatesToReverseEndpoint(): void
    {
        $response = new JsonMockResponse(['address' => ['city' => 'Gniezno']]);

        $this->createClient($response)->reverseGeocode($this->reverseGeocodingDto());

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertStringStartsWith(self::BASE_URL.'/reverse?', $response->getRequestUrl());
        $this->assertSame(
            [
                'lat' => '52.5361',
                'lon' => '17.5934',
                'format' => 'json',
                'accept-language' => 'pl',
            ],
            $this->requestQuery($response),
        );
    }

    #[DataProvider('cityFallbackProvider')]
    public function testReverseGeocodeFallsBackToSmallerLocalities(array $locality, string $expectedCity): void
    {
        $response = new JsonMockResponse(['address' => $locality]);

        $address = $this->createClient($response)->reverseGeocode($this->reverseGeocodingDto());

        $this->assertSame($expectedCity, $address->city);
    }

    public function testReverseGeocodeTrimsStreetWithoutHouseNumber(): void
    {
        $response = new JsonMockResponse(['address' => ['road' => 'Krótka', 'city' => 'Gniezno']]);

        $address = $this->createClient($response)->reverseGeocode($this->reverseGeocodingDto());

        $this->assertSame('Krótka', $address->street);
    }

    public function testReverseGeocodeThrowsWhenNominatimReturnsError(): void
    {
        $this->expectException(AddressNotFoundException::class);

        $this->createClient(new JsonMockResponse(['error' => 'Unable to geocode']))
            ->reverseGeocode($this->reverseGeocodingDto());
    }

    public function testPropagatesServerErrors(): void
    {
        $this->expectException(ServerExceptionInterface::class);

        $this->createClient(new MockResponse('', ['http_code' => 503]))
            ->geocode($this->geocodingDto());
    }

    private function createClient(MockResponse $response): NominatimClient
    {
        return new NominatimClient(new MockHttpClient($response, self::BASE_URL));
    }

    private function requestQuery(MockResponse $response): array
    {
        parse_str((string) parse_url($response->getRequestUrl(), \PHP_URL_QUERY), $query);

        return $query;
    }

    private function geocodingDto(?string $postalCode = '62-200'): GeocodingRequestDto
    {
        $dto = new GeocodingRequestDto();
        $dto->street = 'Krótka 1';
        $dto->city = 'Gniezno';
        $dto->province = 'Wielkopolskie';
        $dto->countrySymbol = 'PL';
        $dto->postalCode = $postalCode;

        return $dto;
    }

    private function reverseGeocodingDto(): ReverseGeocodingRequestDto
    {
        $dto = new ReverseGeocodingRequestDto();
        $dto->latitude = 52.5361;
        $dto->longitude = 17.5934;

        return $dto;
    }
}
