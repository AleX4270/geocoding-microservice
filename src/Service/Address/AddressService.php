<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Dto\Address\CreateAddressDto;
use App\Entity\Address;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Province;
use App\Repository\AddressRepository;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use App\Repository\ProvinceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AddressService
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly ProvinceRepository $provinceRepository,
        private readonly CityRepository $cityRepository,
        private readonly AddressRepository $addressRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {}

    public function create(CreateAddressDto $dto): Address {
        $errors = $this->validator->validate($dto);
        if(count($errors) > 0) {
            throw new ValidationFailedException($dto, $errors);
        }

        return $this->entityManager->wrapInTransaction(function() use ($dto): Address {
            $country = $this->countryRepository->findOneBy(['symbol' => $dto->countrySymbol]);
            if (empty($country)) {
                $country = new Country();
                $country->setSymbol($dto->countrySymbol);
                $this->entityManager->persist($country);
            }

            $province = $this->provinceRepository->findOneBy(['name' => $dto->province]);
            if (empty($province)) {
                $province = new Province();
                $province->setName($dto->province);
                $province->setCountry($country);
                $this->entityManager->persist($province);
                $this->entityManager->flush();
            }

            $city = $this->cityRepository->findOneBy(['name' => $dto->city, 'province' => $province]);
            if (empty($city)) {
                $city = new City();
                $city->setName($dto->city);
                $city->setProvince($province);
                $this->entityManager->persist($city);
                $this->entityManager->flush();
            }

            $address = $this->addressRepository->findOneBy(['address' => $dto->address, 'city' => $city]);
            if (empty($address)) {
                $address = new Address();
                $address->setAddress($dto->address);
                $address->setPostalCode($dto->postalCode);
                $address->setCity($city);
                $address->setCoordinates($dto->coordinates);
                $this->entityManager->persist($address);
            }

            $this->entityManager->flush();

            return $address;
        });
    }
}
