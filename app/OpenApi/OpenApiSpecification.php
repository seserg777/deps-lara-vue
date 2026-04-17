<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        title: 'Catalog API',
        version: '1.0.0',
        description: 'JSON API for catalog search and categories.',
    ),
    servers: [
        new OA\Server(url: '/', description: 'Current application host'),
    ],
)]
#[OA\Tag(name: 'Catalog', description: 'Catalog endpoints')]
final class OpenApiSpecification {}
