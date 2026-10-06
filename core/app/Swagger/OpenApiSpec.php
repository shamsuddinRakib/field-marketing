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
#[OA\Tag(
    name: 'Fund Requests',
    description: 'MR fund request endpoints'
)]
class OpenApiSpec
{
}