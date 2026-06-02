<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abandoned Cart Recovery
    |--------------------------------------------------------------------------
    */

    // Master switch for the recovery feature.
    'recovery_enabled' => (bool) env('CART_RECOVERY_ENABLED', true),

    // A cart with an email + items is considered abandoned after this many
    // minutes of inactivity (no add/update/remove and not converted).
    'abandon_after_minutes' => (int) env('CART_ABANDON_AFTER_MINUTES', 60),

    // Stop recovering carts older than this (avoid emailing stale carts).
    'abandon_max_age_hours' => (int) env('CART_ABANDON_MAX_AGE_HOURS', 72),

    // Discount offered in the recovery email.
    'recovery_discount_percent' => (int) env('CART_RECOVERY_DISCOUNT_PERCENT', 10),

    // How long the minted recovery coupon stays valid.
    'recovery_coupon_ttl_hours' => (int) env('CART_RECOVERY_COUPON_TTL_HOURS', 48),

    // Max carts processed per scheduler tick (batching).
    'recovery_batch_size' => (int) env('CART_RECOVERY_BATCH_SIZE', 50),

];
