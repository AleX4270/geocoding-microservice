<?php

declare(strict_types=1);

namespace App\Service\ReverseGeocoding;

use App\Dto\Request\ReverseGeocoding\ReverseGeocodingRequestDto;
use App\Entity\Address;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Province;
use App\Enum\NominatimEndpoint;
use App\Exception\ReverseGeocoding\AddressNotFoundException;
use App\Repository\AddressRepository;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use App\Repository\ProvinceRepository;
use App\Type\PostalAddress;
use App\ValueObject\Coordinates;
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
    ) {
    }

    public function reverseGeocode(ReverseGeocodingRequestDto $dto): PostalAddress
    {
        // // TODO: Check if the result is already in the database
        // $address = $this->addressRepository->findByAllParameters([
        //     'address' => $dto->street,
        //     'city' => $dto->city,
        //     'country' => $dto->countrySymbol,
        //     'postalCode' => $dto->postalCode,
        // ]);

        // // TODO: Move the nominatim geocoding to the external service

        // if (!empty($address)) {
        //     return $address->getCoordinates();
        // }

        $baseUrl = $this->nominatimApiUrl . NominatimEndpoint::REVERSE_GEOCODE->value;
        $queryParams = [
            'lat' => $dto->latitude,
            'lon' => $dto->longitude,
            'format' => 'json',
        ];

        $response = $this->httpClient->request('GET', $baseUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'query' => $queryParams,
        ]);

        $data = $response->toArray();

        if (empty($data)) {
            throw new AddressNotFoundException();
        }

        // TODO: 1. Map the data to PostalAddress type, create nominatim client class and interface

        // return $data;

        // // TODO: Save result to the database
        // $country = $this->countryRepository->findOneBy(['symbol' => $dto->countrySymbol]);
        // if (empty($country)) {
        //     $country = new Country();
        //     $country->setSymbol($dto->countrySymbol);
        //     $this->entityManager->persist($country);
        // }

        // $province = $this->provinceRepository->findOneBy(['name' => $dto->province]);
        // if (empty($province)) {
        //     $province = new Province();
        //     $province->setName($dto->province);
        //     $province->setCountry($country);
        //     $this->entityManager->persist($province);
        // }

        // $city = $this->cityRepository->findOneBy(['name' => $dto->city]);
        // if (empty($city)) {
        //     $city = new City();
        //     $city->setName($dto->city);
        //     $city->setProvince($province);
        //     $this->entityManager->persist($city);
        // }

        // $address = $this->addressRepository->findOneBy(['address' => $dto->street]);
        // if (empty($address)) {
        //     $address = new Address();
        //     $address->setAddress($dto->street);
        //     $address->setCity($city);
        //     $address->setCoordinates(new Coordinates(
        //         latitude: (float) $data[0]['lat'],
        //         longitude: (float) $data[0]['lon'],
        //     ));
        //     $this->entityManager->persist($address);
        // }

        // $this->entityManager->flush();

        // return new Coordinates(
        //     latitude: (float) $data[0]['lat'],
        //     longitude: (float) $data[0]['lon'],
        // );
    }
}
