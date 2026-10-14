<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthRegisterRequest',
    type: 'object',
    required: ['name', 'phone', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Rahim Uddin'),
        new OA\Property(property: 'phone', type: 'string', example: '01700000000'),
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'rahim@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: '123456'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: '123456'),
    ]
)]
class AuthRegisterRequest {}
