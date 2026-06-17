<?php

declare(strict_types=1);

return [
    'secret_key' => env('STRIPE_SECRET_KEY', ''),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'renewal_price_id' => env('STRIPE_RENEWAL_PRICE_ID', ''),
];
