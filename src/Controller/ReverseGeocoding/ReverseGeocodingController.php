<?php

declare(strict_types=1);

namespace App\Controller\ReverseGeocoding;

use App\Dto\Request\ReverseGeocoding\ReverseGeocodingRequestDto;
use App\Enum\HttpStatus;
use App\Response\ApiJsonResponse;
use App\Service\ReverseGeocoding\ReverseGeocodingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ReverseGeocodingController extends AbstractController
{
    public function __construct(
        private readonly ReverseGeocodingService $service,
    ) {
    }

    #[Route('/reverse-geocoding', methods: 'GET')]
    public function index(#[MapQueryString()] ReverseGeocodingRequestDto $params): ApiJsonResponse
    {
        return new ApiJsonResponse(
            data: $this->service->reverseGeocode($params),
            status: HttpStatus::OK,
            message: 'Success',
        );
    }
}
