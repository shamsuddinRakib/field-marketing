<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BookReturnStoreRequest',
    type: 'object',
    required: ['institution_id', 'product_id', 'issued_quantity', 'returned_quantity'],
    properties: [
        new OA\Property(property: 'institution_id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'issued_quantity', type: 'integer', minimum: 1, example: 10),
        new OA\Property(property: 'returned_quantity', type: 'integer', minimum: 1, example: 4, description: 'Must be <= issued_quantity'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Returned leftover books'),
    ]
)]
class BookReturnStoreRequest {}
