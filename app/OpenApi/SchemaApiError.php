<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiError',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'code', type: 'string', nullable: true),
        new OA\Property(
            property: 'errors',
            description: 'Validation errors keyed by field name',
            type: 'object',
            nullable: true,
        ),
    ],
)]
final class SchemaApiError {}
