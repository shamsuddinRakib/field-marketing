<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderItem',
    type: 'object',
    required: ['id', 'qty'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'qty', type: 'integer', example: 2),
    ]
)]
class OrderItem {}
