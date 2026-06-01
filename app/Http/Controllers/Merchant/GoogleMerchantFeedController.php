<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\Merchant\GoogleMerchantFeedService;
use Illuminate\Http\Response;

class GoogleMerchantFeedController extends Controller
{
    public function show(GoogleMerchantFeedService $feed): Response
    {
        return response($feed->xml(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
