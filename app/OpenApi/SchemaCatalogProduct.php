<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CatalogProduct',
    required: ['id', 'title'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 100),
        new OA\Property(property: 'title', type: 'string', example: 'Product'),
        new OA\Property(
            property: 'price',
            description: 'Numeric price or string from upstream catalog',
            nullable: true,
            oneOf: [
                new OA\Schema(type: 'number', format: 'float'),
                new OA\Schema(type: 'string'),
            ],
        ),
        new OA\Property(property: 'image_url', type: 'string', nullable: true),
    ],
)]
final class SchemaCatalogProduct {}
