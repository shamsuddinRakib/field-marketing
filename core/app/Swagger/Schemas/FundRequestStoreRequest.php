<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FundRequestStoreRequest',
    type: 'object',
    required: ['amount'],
    properties: [
        new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 1, example: 1500, description: 'Required, numeric, min 1'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Travel advance for school visit'),
    ]
)]
class FundRequestStoreRequest {}
