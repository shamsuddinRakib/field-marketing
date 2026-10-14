<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ExpenseItemRequest',
    type: 'object',
    required: ['expense_category_id', 'amount'],
    properties: [
        new OA\Property(property: 'expense_category_id', type: 'integer', example: 1),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Bus fare'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 250),
    ]
)]
class ExpenseItemRequest {}
