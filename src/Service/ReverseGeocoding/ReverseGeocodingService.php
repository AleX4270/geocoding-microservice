<?php

declare(strict_types=1);

namespace App\Service\ReverseGeocoding;

use App\Dto\Request\ReverseGeocoding\ReverseGeocodingRequestDto;
use App\Entity\Address;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Province;
use App\Interface\GeocodingClientInterface;
use App\Repository\AddressRepository;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use App\Repository\ProvinceRepository;
use App\Type\PostalAddress;
use App\Type\ValueObject\Coordinates;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ReverseGeocodingService
{
    public function __construct(
        #[Autowire(env: 'NOMINATIM_API_URL')]
        private readonly string $nominatimApiUrl,
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository $addressRepository,
        private readonly CountryRepository $countryRepository,
        private readonly ProvinceRepository $provinceRepository,
        private readonly CityRepository $cityRepository,
        private readonly GeocodingClientInterface $geocodingClient,
    ) {
    }

    public function reverseGeocode(ReverseGeocodingRequestDto $dto): PostalAddress
    {
        $requestedCoordinates = new Coordinates(
            $dto->latitude,
            $dto->longitude,
        );

        $address = $this->addressRepository->findByCoordinates($requestedCoordinates);

        if (!empty($address)) {
            return new PostalAddress(
                street: $address->getAddress(),
                city: $address->getCity()->getName(),
                province: $address->getCity()->getProvince()->getName(),
                countrySymbol: $address->getCity()->getProvince()->getCountry()->getSymbol(),
                postalCode: 'db!',
            );
        }

        $postalAddress = $this->geocodingClient->reverseGeocode($dto);

        //TODO:  Do not save results with empty data
        $country = $this->countryRepository->findOneBy(['symbol' => $postalAddress->countrySymbol]);
        if (empty($country)) {
            $country = new Country();
            $country->setSymbol($postalAddress->countrySymbol);
            $this->entityManager->persist($country);
        }

        $province = $this->provinceRepository->findOneBy(['name' => $postalAddress->province]);
        if (empty($province)) {
            $province = new Province();
            $province->setName($postalAddress->province);
            $province->setCountry($country);
            $this->entityManager->persist($province);
        }

        $city = $this->cityRepository->findOneBy(['name' => $postalAddress->city]);
        if (empty($city)) {
            $city = new City();
            $city->setName($postalAddress->city);
            $city->setProvince($province);
            $this->entityManager->persist($city);
        }

        $address = $this->addressRepository->findOneBy(['address' => $postalAddress->street]);
        if (empty($address)) {
            $address = new Address();
            $address->setAddress($postalAddress->street);
            $address->setCity($city);
            $address->setCoordinates($requestedCoordinates);
            $this->entityManager->persist($address);
        }

        $this->entityManager->flush();

        return $postalAddress;
    }
}
