<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProfileUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Rahim Uddin'),
        new OA\Property(property: 'username', type: 'string', nullable: true, example: 'rahim01'),
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'rahim@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '01700000000'),
        new OA\Property(property: 'current_password', type: 'string', nullable: true, example: '123456'),
        new OA\Property(property: 'password', type: 'string', nullable: true, example: 'newpass123'),
        new OA\Property(property: 'password_confirmation', type: 'string', nullable: true, example: 'newpass123'),
    ]
)]
class ProfileUpdateRequest {}
