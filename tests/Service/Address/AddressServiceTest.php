<?php

namespace App\Tests\Service\Address;

use App\Dto\Address\CreateAddressDto;
use App\Repository\AddressRepository;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use App\Repository\ProvinceRepository;
use App\Service\Address\AddressService;
use App\Type\ValueObject\Coordinates;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class AddressServiceTest extends KernelTestCase
{   
    private AddressService $service;
    private $container;

    protected function setUp(): void
    {
        $this->container = static::getContainer();
        $this->service = $this->container->get(AddressService::class);
    }

    public static function invalidDtoProvider(): iterable
    {
        $valid = [
            'address' => 'Krótka 1',
            'city' => 'Gniezno',
            'province' => 'Wielkopolskie',
            'countrySymbol' => 'PL',
            'postalCode' => '62-200',
            'coordinates' => new Coordinates(52.5361, 17.5934),
        ];

        yield 'blank address' => [[...$valid, 'address' => '']];
        yield 'blank city' => [[...$valid, 'city' => '']];
        yield 'blank province' => [[...$valid, 'province' => '']];
        yield 'blank country symbol' => [[...$valid, 'countrySymbol' => '']];
    }

    public function testCreatesFullAddressWhenNew(): void
    {
        $dto = new CreateAddressDto(
            address: 'Krótka 1',
            city: 'Gniezno',
            province: 'Mazowieckie',
            countrySymbol: 'PL',
            postalCode: '75-231',
            coordinates: new Coordinates(52.4292009, 17.4884394),
        );

        $result = $this->service->create($dto);

        $this->assertSame(1, $this->container->get(CountryRepository::class)->count());
        $this->assertSame(1, $this->container->get(ProvinceRepository::class)->count());
        $this->assertSame(1, $this->container->get(CityRepository::class)->count());
        $this->assertSame(1, $this->container->get(AddressRepository::class)->count());

        $this->container->get(EntityManagerInterface::class)->clear();
        $stored = $this->container->get(AddressRepository::class)->find($result->getId());

        $this->assertNotNull($stored);
        $this->assertSame($dto->address, $stored->getAddress());
        $this->assertSame($dto->postalCode, $stored->getPostalCode());
        $this->assertEquals($dto->coordinates, $stored->getCoordinates());
        $this->assertSame($dto->city, $stored->getCity()->getName());
        $this->assertSame('mazowieckie', $stored->getCity()->getProvince()->getName());
        $this->assertSame($dto->countrySymbol, $stored->getCity()->getProvince()->getCountry()->getSymbol());
    }

    public function testReusesExistingAddressPartsDuringCreation(): void
    {
        $existing = $this->service->create(new CreateAddressDto(
            address: 'Długa 10',
            city: 'Gniezno',
            province: 'Wielkopolskie',
            countrySymbol: 'PL',
            postalCode: '62-200',
            coordinates: new Coordinates(52.5347, 17.5826),
        ));

        $result = $this->service->create(new CreateAddressDto(
            address: 'Krótka 1',
            city: 'Gniezno',
            province: 'WIELKOPOLSKIE',
            countrySymbol: 'PL',
            postalCode: '62-200',
            coordinates: new Coordinates(52.5361, 17.5934),
        ));

        $this->assertSame(1, $this->container->get(CountryRepository::class)->count());
        $this->assertSame(1, $this->container->get(ProvinceRepository::class)->count());
        $this->assertSame(1, $this->container->get(CityRepository::class)->count());
        $this->assertSame(2, $this->container->get(AddressRepository::class)->count());

        $this->assertSame($existing->getCity()->getId(), $result->getCity()->getId());
    }

    public function testReturnsAddressEntity(): void
    {
        $dto = new CreateAddressDto(
            address: 'Krótka 1',
            city: 'Gniezno',
            province: 'Mazowieckie',
            countrySymbol: 'PL',
            postalCode: '75-231',
            coordinates: new Coordinates(52.4292009, 17.4884394),
        );

        $result = $this->service->create($dto);

        $this->assertNotNull($result->getId());
        $this->assertSame($dto->address, $result->getAddress());
        $this->assertSame($dto->postalCode, $result->getPostalCode());
        $this->assertEquals($dto->coordinates, $result->getCoordinates());
        $this->assertSame($dto->city, $result->getCity()->getName());
        $this->assertSame('mazowieckie', $result->getCity()->getProvince()->getName());
        $this->assertSame($dto->countrySymbol, $result->getCity()->getProvince()->getCountry()->getSymbol());
    }

    #[DataProvider('invalidDtoProvider')]
    public function testThrowsErrorForInvalidDtoParams(array $params): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->service->create(new CreateAddressDto(...$params));
    }

    public function testCreatesNothingWhenErrorIsThrown(): void
    {
        $dto = new CreateAddressDto(
            address: 'Krótka 1',
            city: 'Gniezno',
            province: 'Wielkopolskie',
            countrySymbol: 'PL',
            postalCode: str_repeat('1', 33),
            coordinates: new Coordinates(52.5361, 17.5934),
        );

        try {
            $this->service->create($dto);
        } catch (DriverException) {
        }

        $this->assertSame(0, $this->container->get(CountryRepository::class)->count());
        $this->assertSame(0, $this->container->get(ProvinceRepository::class)->count());
        $this->assertSame(0, $this->container->get(CityRepository::class)->count());
        $this->assertSame(0, $this->container->get(AddressRepository::class)->count());
    }
}
