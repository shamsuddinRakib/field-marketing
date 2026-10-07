<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ExpenseStoreRequest',
    type: 'object',
    required: ['daily_visit_id', 'branch_id', 'name', 'expense_date', 'status', 'items'],
    properties: [
        new OA\Property(property: 'daily_visit_id', type: 'integer', example: 1),
        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
        new OA\Property(property: 'invoice_no', type: 'string', nullable: true, example: 'EXP-001'),
        new OA\Property(property: 'posted_at', type: 'string', format: 'date-time', nullable: true, example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'name', type: 'string', example: 'Dhaka visit expense'),
        new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2025-01-15'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Bus + food'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'posted', 'cancelled'], example: 'draft'),
        new OA\Property(property: 'attachment', type: 'string', nullable: true, example: 'bill.jpg'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ExpenseItemRequest')
        ),
    ]
)]
class ExpenseStoreRequest {}
