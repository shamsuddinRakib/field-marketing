<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BookRequestProduct',
    type: 'object',
    description: 'Product object as returned through BookRequestResource (whenLoaded product)',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'English Grammar Book'),
        new OA\Property(property: 'slug', type: 'string', example: 'english-grammar-book'),
        new OA\Property(property: 'sku', type: 'string', nullable: true, example: 'BK-001'),
        new OA\Property(property: 'brand_id', type: 'integer', nullable: true, example: 2),
    ]
)]
#[OA\Schema(
    schema: 'BookRequestResource',
    type: 'object',
    description: 'Exact shape of App\Http\Resources\BookRequestResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 10),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Need for school supply'),
        new OA\Property(property: 'request_date', type: 'string', format: 'date', nullable: true, example: '2025-01-15'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected'], example: 'pending'),
        new OA\Property(property: 'product', ref: '#/components/schemas/BookRequestProduct', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'BookRequestPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Book requests fetched successfully.'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/BookRequestResource')
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
    schema: 'BookRequestSingleResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Book request fetched successfully.'),
        new OA\Property(property: 'data', ref: '#/components/schemas/BookRequestResource'),
    ]
)]
class BookRequestResourceSchema {}
