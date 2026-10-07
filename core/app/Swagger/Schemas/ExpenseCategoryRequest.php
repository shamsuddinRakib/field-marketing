<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ExpenseCategoryRequest',
    type: 'object',
    required: ['name', 'is_active'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Transport'),
        new OA\Property(property: 'slug', type: 'string', nullable: true, example: 'transport'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ]
)]
class ExpenseCategoryRequest {}
