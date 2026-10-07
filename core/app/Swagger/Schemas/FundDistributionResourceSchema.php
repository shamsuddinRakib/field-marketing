<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FundDistributionResource',
    type: 'object',
    description: 'Exact shape of App\Http\Resources\FundDistributionResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 5),
        new OA\Property(property: 'amount', type: 'integer', example: 1500),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Monthly fund distribution'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'delivered'], example: 'pending'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true, example: '2025-01-15T10:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, example: '2025-01-15T10:00:00.000000Z'),
    ]
)]
#[OA\Schema(
    schema: 'FundDistributionPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Fund distributions fetched successfully.'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/FundDistributionResource')
        ),
        new OA\Property(
            property: 'pagination',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                new OA\Property(property: 'total', type: 'integer', example: 25),
                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'to', type: 'integer', nullable: true, example: 10),
            ],
            type: 'object'
        ),
    ]
)]
#[OA\Schema(
    schema: 'FundDistributionSingleResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Fund distribution fetched successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/FundDistributionResource'),
    ]
)]
class FundDistributionResourceSchema {}
