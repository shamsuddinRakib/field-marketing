<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MrLoginRequest',
    type: 'object',
    required: ['login', 'password'],
    properties: [
        new OA\Property(property: 'login', type: 'string', example: 'sujitkarmoker59@gmail.com', description: 'Phone or email'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: '12345678'),
    ]
)]
class MrLoginRequest {}
