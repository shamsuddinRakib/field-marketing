<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DailyVisitStoreRequest',
    type: 'object',
    required: ['teacher_id', 'visit_date', 'status'],
    properties: [
        new OA\Property(property: 'teacher_id', type: 'integer', example: 1),
        new OA\Property(property: 'library_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'visit_date', type: 'string', format: 'date', example: '2026-10-09'),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Discussed new books'),
    ]
)]
class DailyVisitStoreRequest {}
