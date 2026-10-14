<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderCreateRequest',
    type: 'object',
    required: ['name', 'phone', 'items', 'shipping_address', 'city'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Rahim Uddin'),
        new OA\Property(property: 'phone', type: 'string', example: '01700000000'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItem')
        ),
        new OA\Property(property: 'coupon_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'shipping_address', type: 'string', example: 'House 12, Road 5, Dhaka'),
        new OA\Property(property: 'city', type: 'string', example: 'Dhaka'),
        new OA\Property(property: 'postal_code', type: 'string', nullable: true, example: '1207'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Please call before delivery'),
    ]
)]
class OrderCreateRequest {}
