<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cart): View
    {
        return view('storefront.cart.index', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'branches' => \App\Models\Branch::active()->get(),
        ]);
    }
}
