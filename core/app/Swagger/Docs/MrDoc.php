<?php

namespace App\Swagger\Docs;

use OpenApi\Attributes as OA;

/**
 * MR (Marketing Representative) APIs — controllers stay clean.
 */
class MrDoc
{
    #[OA\Post(
        path: '/mr/login',
        summary: 'MR login with phone/email + password',
        tags: ['MR Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MrLoginRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 403, description: 'Inactive / not MR'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function mrLogin() {}

    #[OA\Get(
        path: '/mr/me',
        summary: 'Get logged-in MR basic info',
        security: [['bearerAuth' => []]],
        tags: ['MR Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Current user + MR info'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function mrMe() {}

    #[OA\Get(
        path: '/mr/profile',
        summary: 'Get MR full profile',
        security: [['bearerAuth' => []]],
        tags: ['MR Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Profile retrieved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Profile not found'),
        ]
    )]
    public function mrProfile() {}

    #[OA\Put(
        path: '/mr/credentials',
        summary: 'Update MR email/phone/password',
        security: [['bearerAuth' => []]],
        tags: ['MR Auth'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/MrCredentialsUpdateRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Credentials updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error / wrong current password'),
        ]
    )]
    public function mrCredentials() {}

    #[OA\Post(
        path: '/mr/logout',
        summary: 'MR logout (current token)',
        security: [['bearerAuth' => []]],
        tags: ['MR Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logout successful'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function mrLogout() {}

    #[OA\Get(
        path: '/mr/dashboard',
        summary: 'Get MR dashboard summary',
        description: 'Returns today total visit count and today total posted expense for authenticated MR.',
        security: [['bearerAuth' => []]],
        tags: ['MR Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard data fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Dashboard data fetched successfully.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-10-06'),
                                new OA\Property(property: 'total_visit', type: 'integer', example: 5),
                                new OA\Property(property: 'total_expense', type: 'number', format: 'float', example: 1200.5),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function dashboard() {}

    #[OA\Get(
        path: '/mr/daily-visits',
        summary: 'List MR daily visits with filters',
        security: [['bearerAuth' => []]],
        tags: ['MR DailyVisit'],
        parameters: [
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), example: '2025-01-15'),
            new OA\Parameter(name: 'month', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: '2025-01', description: 'YYYY-MM'),
            new OA\Parameter(name: 'from_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), example: '2025-01-01'),
            new OA\Parameter(name: 'to_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), example: '2025-01-31'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Visit list with summary + pagination'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function dailyVisitList() {}

    #[OA\Post(
        path: '/mr/daily-visits',
        summary: 'Create MR daily visit',
        security: [['bearerAuth' => []]],
        tags: ['MR DailyVisit'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DailyVisitStoreRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Visit created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error / duplicate visit'),
        ]
    )]
    public function dailyVisitStore() {}

    #[OA\Get(
        path: '/mr/daily-visits/{id}',
        summary: 'Get single daily visit',
        security: [['bearerAuth' => []]],
        tags: ['MR DailyVisit'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Visit details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function dailyVisitShow() {}

    #[OA\Get(
        path: '/mr/expense-categories',
        summary: 'List active expense categories',
        security: [['bearerAuth' => []]],
        tags: ['MR ExpenseCategory'],
        responses: [
            new OA\Response(response: 200, description: 'Category list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function expenseCategoryList() {}

    #[OA\Post(
        path: '/mr/expense-categories',
        summary: 'Create expense category',
        security: [['bearerAuth' => []]],
        tags: ['MR ExpenseCategory'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ExpenseCategoryRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function expenseCategoryStore() {}

    #[OA\Put(
        path: '/mr/expense-categories/{id}',
        summary: 'Update expense category',
        security: [['bearerAuth' => []]],
        tags: ['MR ExpenseCategory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ExpenseCategoryRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function expenseCategoryUpdate() {}

    #[OA\Delete(
        path: '/mr/expense-categories/{id}',
        summary: 'Delete expense category',
        security: [['bearerAuth' => []]],
        tags: ['MR ExpenseCategory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function expenseCategoryDelete() {}

    #[OA\Post(
        path: '/mr/expenses',
        summary: 'Create expense with items for a daily visit',
        security: [['bearerAuth' => []]],
        tags: ['MR Expense'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ExpenseStoreRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Expense created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error / visit not yours'),
        ]
    )]
    public function expenseStore() {}

    #[OA\Get(
        path: '/mr/book-requests',
        summary: 'List my book requests (paginated, via BookRequestResource)',
        security: [['bearerAuth' => []]],
        tags: ['MR BookRequest'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected']), example: 'pending', description: 'Filter by status'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100), example: 10),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated book request list through BookRequestResource',
                content: new OA\JsonContent(ref: '#/components/schemas/BookRequestPaginatedResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'MR profile not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bookRequestList() {}

    #[OA\Post(
        path: '/mr/book-requests',
        summary: 'Create book request',
        security: [['bearerAuth' => []]],
        tags: ['MR BookRequest'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookRequestStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Book request created (BookRequestResource)',
                content: new OA\JsonContent(ref: '#/components/schemas/BookRequestSingleResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'MR profile not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bookRequestStore() {}

    #[OA\Get(
        path: '/mr/book-requests/{id}',
        summary: 'Get single book request (BookRequestResource)',
        security: [['bearerAuth' => []]],
        tags: ['MR BookRequest'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Book request details through BookRequestResource',
                content: new OA\JsonContent(ref: '#/components/schemas/BookRequestSingleResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function bookRequestShow() {}

    #[OA\Put(
        path: '/mr/book-requests/{id}',
        summary: 'Update pending book request',
        security: [['bearerAuth' => []]],
        tags: ['MR BookRequest'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookRequestStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated (BookRequestResource)',
                content: new OA\JsonContent(ref: '#/components/schemas/BookRequestSingleResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending can be edited'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bookRequestUpdate() {}

    #[OA\Delete(
        path: '/mr/book-requests/{id}',
        summary: 'Delete pending book request',
        security: [['bearerAuth' => []]],
        tags: ['MR BookRequest'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending can be deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function bookRequestDelete() {}

    #[OA\Get(
        path: '/mr/book-returns',
        summary: 'List my book returns',
        security: [['bearerAuth' => []]],
        tags: ['MR BookReturn'],
        responses: [
            new OA\Response(response: 200, description: 'Book return list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'MR profile not found'),
        ]
    )]
    public function bookReturnList() {}

    #[OA\Post(
        path: '/mr/book-returns',
        summary: 'Create book return',
        security: [['bearerAuth' => []]],
        tags: ['MR BookReturn'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookReturnStoreRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Book return created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'MR profile not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bookReturnStore() {}

    #[OA\Get(
        path: '/mr/book-returns/{id}',
        summary: 'Get single book return',
        security: [['bearerAuth' => []]],
        tags: ['MR BookReturn'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Book return details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function bookReturnShow() {}

    #[OA\Put(
        path: '/mr/book-returns/{id}',
        summary: 'Update pending book return',
        security: [['bearerAuth' => []]],
        tags: ['MR BookReturn'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookReturnStoreRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending can be edited'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bookReturnUpdate() {}

    #[OA\Delete(
        path: '/mr/book-returns/{id}',
        summary: 'Delete pending book return',
        security: [['bearerAuth' => []]],
        tags: ['MR BookReturn'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending can be deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function bookReturnDelete() {}

    #[OA\Get(
        path: '/mr/fund-requests',
        summary: 'List my fund requests (paginated, via FundRequestResource)',
        security: [['bearerAuth' => []]],
        tags: ['MR FundRequest'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected']), example: 'pending', description: 'Filter by status'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100), example: 10),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated fund request list through FundRequestResource',
                content: new OA\JsonContent(ref: '#/components/schemas/FundRequestPaginatedResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function fundRequestList() {}

    #[OA\Post(
        path: '/mr/fund-requests',
        summary: 'Create fund request',
        security: [['bearerAuth' => []]],
        tags: ['MR FundRequest'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/FundRequestStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Fund request created (FundRequestResource)',
                content: new OA\JsonContent(ref: '#/components/schemas/FundRequestSingleResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function fundRequestStore() {}

    #[OA\Get(
        path: '/mr/fund-requests/{id}',
        summary: 'Get single fund request (FundRequestResource)',
        security: [['bearerAuth' => []]],
        tags: ['MR FundRequest'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fund request details through FundRequestResource',
                content: new OA\JsonContent(ref: '#/components/schemas/FundRequestSingleResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function fundRequestShow() {}

    #[OA\Put(
        path: '/mr/fund-requests/{id}',
        summary: 'Update pending fund request',
        security: [['bearerAuth' => []]],
        tags: ['MR FundRequest'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/FundRequestStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated (FundRequestResource)',
                content: new OA\JsonContent(ref: '#/components/schemas/FundRequestSingleResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending can be edited'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function fundRequestUpdate() {}

    #[OA\Delete(
        path: '/mr/fund-requests/{id}',
        summary: 'Delete pending fund request',
        security: [['bearerAuth' => []]],
        tags: ['MR FundRequest'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only pending can be deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function fundRequestDelete() {}

    #[OA\Get(
        path: '/mr/fund-distributions',
        summary: 'List my fund distributions (paginated, via FundDistributionResource)',
        security: [['bearerAuth' => []]],
        tags: ['MR FundDistribution'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'delivered']), example: 'pending', description: 'Filter by status'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100), example: 10),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated fund distribution list through FundDistributionResource',
                content: new OA\JsonContent(ref: '#/components/schemas/FundDistributionPaginatedResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function fundDistributionList() {}

    #[OA\Get(
        path: '/mr/fund-distributions/{id}',
        summary: 'Get single fund distribution (FundDistributionResource)',
        security: [['bearerAuth' => []]],
        tags: ['MR FundDistribution'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fund distribution details through FundDistributionResource',
                content: new OA\JsonContent(ref: '#/components/schemas/FundDistributionSingleResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function fundDistributionShow() {}
}
