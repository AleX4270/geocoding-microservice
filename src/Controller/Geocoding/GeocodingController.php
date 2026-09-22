<?php

declare(strict_types=1);

namespace App\Controller\Geocoding;

use App\Dto\Request\Geocoding\GeocodingRequestDto;
use App\Enum\HttpStatus;
use App\Response\ApiJsonResponse;
use App\Service\Geocoding\GeocodingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class GeocodingController extends AbstractController
{
    public function __construct(
        private readonly GeocodingService $service,
    ) {
    }

    #[Route('/geocoding', methods: 'GET')]
    public function index(#[MapQueryString()] GeocodingRequestDto $params): ApiJsonResponse
    {
        return new ApiJsonResponse(
            data: $this->service->geocode($params),
            status: HttpStatus::OK,
            message: 'Success', // TODO: i18n
        );
    }
}
