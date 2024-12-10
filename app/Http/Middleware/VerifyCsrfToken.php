<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    // app/Http/Middleware/VerifyCsrfToken.php
    protected $except = [
        'stripe/*',
        'stripe/webhook',
        '/stripe/webhook'  // Add multiple variations to be safe
    ];
}
