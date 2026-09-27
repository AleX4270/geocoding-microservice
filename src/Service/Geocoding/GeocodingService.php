<?php

declare(strict_types=1);

namespace App\Service\Geocoding;

use App\Dto\Address\CreateAddressDto;
use App\Dto\Request\Geocoding\GeocodingRequestDto;
use App\Interface\GeocodingClientInterface;
use App\Repository\AddressRepository;
use App\Service\Address\AddressService;
use App\Type\ValueObject\Coordinates;

final class GeocodingService
{
    public function __construct(
        private readonly AddressRepository $addressRepository,
        private readonly GeocodingClientInterface $geocodingClient,
        private readonly AddressService $addressService,
    ) {
    }

    public function geocode(GeocodingRequestDto $dto): Coordinates
    {
        $address = $this->addressRepository->findByAllParameters([
            'address' => $dto->street,
            'city' => $dto->city,
            'country' => $dto->countrySymbol,
            'postalCode' => $dto->postalCode,
        ]);

        if (!empty($address)) {
            return $address->getCoordinates();
        }

        $coordinates = $this->geocodingClient->geocode($dto);

        $this->addressService->create(new CreateAddressDto(
            address: $dto->street,
            city: $dto->city,
            province: $dto->province,
            countrySymbol: $dto->countrySymbol,
            coordinates: $coordinates,
            postalCode: $dto->postalCode,
        ));

        return $coordinates;
    }
}
