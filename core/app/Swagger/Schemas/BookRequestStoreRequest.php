<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BookRequestStoreRequest',
    type: 'object',
    required: ['product_id', 'quantity'],
    properties: [
        new OA\Property(property: 'product_id', type: 'integer', example: 1, description: 'Must exist in products table'),
        new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 1000000, example: 10),
        new OA\Property(property: 'note', type: 'string', nullable: true, maxLength: 1000, example: 'Need for school supply'),
        new OA\Property(property: 'request_date', type: 'string', format: 'date', nullable: true, example: '2025-01-15', description: 'Defaults to today if omitted'),
    ]
)]
class BookRequestStoreRequest {}
