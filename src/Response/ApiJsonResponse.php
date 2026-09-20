<?php

declare(strict_types=1);

namespace App\Response;

use App\Enum\HttpStatus;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiJsonResponse extends JsonResponse
{
    public function __construct(
        mixed $data = null,
        HttpStatus $status = HttpStatus::OK,
        ?string $message = null,
    ) {
        parent::__construct(
            [
                'data' => $data,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s'),
            ],
            $status->value,
        );
    }
}
