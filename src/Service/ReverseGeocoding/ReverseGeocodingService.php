<?php

declare(strict_types=1);

namespace App\Service\ReverseGeocoding;

use App\Dto\Address\CreateAddressDto;
use App\Dto\Request\ReverseGeocoding\ReverseGeocodingRequestDto;
use App\Interface\GeocodingClientInterface;
use App\Repository\AddressRepository;
use App\Service\Address\AddressService;
use App\Type\PostalAddress;
use App\Type\ValueObject\Coordinates;

final class ReverseGeocodingService
{
    public function __construct(
        private readonly AddressRepository $addressRepository,
        private readonly GeocodingClientInterface $geocodingClient,
        private readonly AddressService $addressService,
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
                postalCode: $address->getPostalCode(),
            );
        }

        $postalAddress = $this->geocodingClient->reverseGeocode($dto);

        $this->addressService->create(new CreateAddressDto(
            address: $postalAddress->street,
            city: $postalAddress->city,
            province: $postalAddress->province,
            countrySymbol: $postalAddress->countrySymbol,
            coordinates: $requestedCoordinates,
            postalCode: $postalAddress->postalCode,
        ));

        return $postalAddress;
    }
}
