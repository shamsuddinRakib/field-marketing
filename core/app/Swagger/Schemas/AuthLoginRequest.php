<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthLoginRequest',
    type: 'object',
    required: ['phone', 'password'],
    properties: [
        new OA\Property(property: 'login', type: 'string', example: '01867188177', description: 'Phone (controller uses phone field)'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: '12345678'),
    ]
)]
class AuthLoginRequest {}
