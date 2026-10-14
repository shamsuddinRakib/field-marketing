<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CouponRequest',
    type: 'object',
    required: ['code'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'DISCOUNT10'),
    ]
)]
class CouponRequest {}
