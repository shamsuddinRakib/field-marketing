<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Field Marketing Management API',
    description: 'API documentation for the Field Marketing Management System'
)]
#[OA\Server(
    url: 'http://localhost/field-marketing/api',
    description: 'Local development server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Bearer',
    description: 'Enter Sanctum token as: Bearer <token>'
)]
// #[OA\Tag(
//     name: 'Fund Requests',
//     description: 'MR fund request endpoints'
// )]
#[OA\Tag(name: 'Auth', description: 'Customer authentication (shop)')]
#[OA\Tag(name: 'Profile', description: 'Customer profile')]
#[OA\Tag(name: 'Category', description: 'Product categories')]
#[OA\Tag(name: 'Product', description: 'Products')]
#[OA\Tag(name: 'Order', description: 'Orders')]
#[OA\Tag(name: 'Coupon', description: 'Coupons')]
#[OA\Tag(name: 'WebsiteSetting', description: 'Website settings')]
#[OA\Tag(name: 'Offer', description: 'Offers')]
#[OA\Tag(name: 'MR Auth', description: 'Marketing Representative authentication')]
#[OA\Tag(name: 'MR Dashboard', description: 'MR dashboard summary (today visit + expense)')]
#[OA\Tag(name: 'MR DailyVisit', description: 'MR daily visits (teacher/library)')]
#[OA\Tag(name: 'MR ExpenseCategory', description: 'MR expense categories')]
#[OA\Tag(name: 'MR Expense', description: 'MR expenses')]
#[OA\Tag(name: 'MR BookRequest', description: 'MR book requests')]
#[OA\Tag(name: 'MR BookReturn', description: 'MR book returns')]
#[OA\Tag(name: 'MR FundRequest', description: 'MR fund requests (auth user scoped)')]
#[OA\Tag(name: 'MR FundDistribution', description: 'MR fund distributions (read-only, auth user scoped)')]
class OpenApiSpec
{
}