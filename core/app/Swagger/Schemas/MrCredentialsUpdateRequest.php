<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MrCredentialsUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'email', type: 'string', nullable: true, example: 'mr@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '01700000000'),
        new OA\Property(property: 'current_password', type: 'string', nullable: true, example: '123456'),
        new OA\Property(property: 'new_password', type: 'string', nullable: true, example: 'newpass123'),
        new OA\Property(property: 'new_password_confirmation', type: 'string', nullable: true, example: 'newpass123'),
    ]
)]
class MrCredentialsUpdateRequest {}
