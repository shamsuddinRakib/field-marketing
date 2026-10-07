<?php

namespace App\Swagger\Docs;

use OpenApi\Attributes as OA;

/**
 * Shop Auth + Profile + Category + Product + Order + Coupon + WebsiteSetting + Offer
 * Controllers stay clean — all OA annotations live here.
 */
class ShopDoc
{
    #[OA\Post(
        path: '/login',
        summary: 'Customer login',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthLoginRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function customerLogin() {}

    #[OA\Post(
        path: '/register',
        summary: 'Customer register',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AuthRegisterRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Registration successful'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function customerRegister() {}

    #[OA\Post(
        path: '/logout',
        summary: 'Customer logout',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function customerLogout() {}

    #[OA\Get(
        path: '/profile',
        summary: 'Get authenticated customer profile',
        security: [['bearerAuth' => []]],
        tags: ['Profile'],
        responses: [
            new OA\Response(response: 200, description: 'Profile data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function profileShow() {}

    #[OA\Put(
        path: '/profile',
        summary: 'Update customer profile',
        security: [['bearerAuth' => []]],
        tags: ['Profile'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/ProfileUpdateRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function profileUpdate() {}

    #[OA\Get(
        path: '/categories',
        summary: 'Get active categories',
        tags: ['Category'],
        responses: [
            new OA\Response(response: 200, description: 'Category list'),
            new OA\Response(response: 404, description: 'Categories not found'),
        ]
    )]
    public function categories() {}

    #[OA\Get(
        path: '/products',
        summary: 'Get product list with filter/sort',
        tags: ['Product'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'books', description: 'Category slug'),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'khata', description: 'Search by product name'),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['price_asc', 'price_desc', 'latest', 'oldest']), example: 'latest'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product paginated list'),
        ]
    )]
    public function products() {}

    #[OA\Get(
        path: '/products/{slug}',
        summary: 'Get single product details',
        tags: ['Product'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'my-product-slug'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product details'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function productShow() {}

    #[OA\Post(
        path: '/orders',
        summary: 'Create new order (guest + logged in)',
        tags: ['Order'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/OrderCreateRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Order created'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Order not created'),
        ]
    )]
    public function orderCreate() {}

    #[OA\Get(
        path: '/user/orders',
        summary: 'Get logged-in customer orders',
        security: [['bearerAuth' => []]],
        tags: ['Order'],
        responses: [
            new OA\Response(response: 200, description: 'Order list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function userOrders() {}

    #[OA\Get(
        path: '/user/orders/{invoice_no}',
        summary: 'Get single order details (logged in)',
        security: [['bearerAuth' => []]],
        tags: ['Order'],
        parameters: [
            new OA\Parameter(name: 'invoice_no', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'SMG-20250101-ABC123'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function orderShow() {}

    #[OA\Get(
        path: '/orders/track-order/{invoice_no}',
        summary: 'Track order by invoice no (public)',
        tags: ['Order'],
        parameters: [
            new OA\Parameter(name: 'invoice_no', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'SMG-20250101-ABC123'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order tracking info'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function trackOrder() {}

    #[OA\Post(
        path: '/get-coupon',
        summary: 'Get coupon by code',
        tags: ['Coupon'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CouponRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Coupon found / not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function getCoupon() {}

    #[OA\Get(
        path: '/website-settings',
        summary: 'Get website settings',
        tags: ['WebsiteSetting'],
        responses: [
            new OA\Response(response: 200, description: 'Website settings data'),
        ]
    )]
    public function websiteSettings() {}

    #[OA\Get(
        path: '/offers',
        summary: 'Get active offers',
        tags: ['Offer'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'eid-offer', description: 'Filter by offer slug'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Offers list'),
        ]
    )]
    public function offers() {}

    #[OA\Get(
        path: '/offers/{slug}/products',
        summary: 'Get products under an offer',
        tags: ['Offer'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'eid-offer'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Offer products'),
        ]
    )]
    public function offerProducts() {}
}
