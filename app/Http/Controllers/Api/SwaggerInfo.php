<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Arletta Gym API',
    description: 'REST API for Arletta Gym Landing Page — Authentication, Memberships, Trainers, Schedules, Bookings, Payments, Signatures',
    contact: new OA\Contact(email: 'admin@arlettagym.com', name: 'Arletta Gym Developer')
)]
#[OA\Server(url: '/', description: 'Local Development')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    in: 'header',
    name: 'Authorization',
    description: 'Enter token in format: Bearer {your_token}'
)]
#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
#[OA\Tag(name: 'Profile', description: 'User profile endpoints')]
#[OA\Tag(name: 'Memberships', description: 'Membership endpoints')]
#[OA\Tag(name: 'Trainers', description: 'Trainer endpoints')]
#[OA\Tag(name: 'Schedules', description: 'Schedule and class endpoints')]
#[OA\Tag(name: 'Bookings', description: 'Booking endpoints')]
#[OA\Tag(name: 'Waitlist', description: 'Waitlist and online class endpoints')]
#[OA\Tag(name: 'Payments', description: 'Payment endpoints')]
#[OA\Tag(name: 'Signatures', description: 'Digital signature endpoints')]
class SwaggerInfo {}
