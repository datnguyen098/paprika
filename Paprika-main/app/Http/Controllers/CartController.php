<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Branch;
use App\Services\CartService;
use App\Support\DishAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function add(Request $request, Dish $dish, CartService $cart, DishAvailabilityService $availability): RedirectResponse|JsonResponse
    {
        abort_unless($dish->is_active, 404);

        $branch = active_branch() ?: primary_branch();
        $unavailableNow = false;
        if ($branch && ! $availability->check($dish, $branch)->available) {
            $unavailableNow = true;
        }

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['integer'],
            'customization_note' => ['nullable', 'string', 'max:500'],
        ]);

        $cart->add(
            $dish,
            (int) ($data['quantity'] ?? 1),
            $data['option_ids'] ?? [],
            $data['customization_note'] ?? null
        );

        $message = $unavailableNow
            ? __('site.cart.added_unavailable_time_slot')
            : __('site.cart.add_success');

        if (! $request->expectsJson()) {
            return back()->with($unavailableNow ? 'warning' : 'success', $message);
        }

        // Keep existing payload shape; add a soft warning flag for the client.
        $response = $this->cartResponse($request, $cart, $message);
        $payload = $response->getData(true);
        $payload['soft_warning'] = $unavailableNow;
        $payload['warning_code'] = $unavailableNow ? 'unavailable_time_slot_now' : null;
        return response()->json($payload, $response->status());
    }

    public function update(Request $request, CartService $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        $cart->update($data['quantities'] ?? []);

        return $this->cartResponse($request, $cart, __('site.cart.update_success'));
    }

    public function remove(Request $request, string $lineKey, CartService $cart): RedirectResponse|JsonResponse
    {
        $cart->remove($lineKey);

        return $this->cartResponse($request, $cart, __('site.cart.remove_success'));
    }

    private function cartResponse(Request $request, CartService $cart, string $message): RedirectResponse|JsonResponse
    {
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        $items = $cart->items();
        $subtotal = $cart->subtotal();
        $count = $cart->count();
        $deliveryFee = 0;
        $discount = 0;
        $total = $subtotal + $deliveryFee - $discount;

        return response()->json([
            'message' => $message,
            'count' => $count,
            'subtotal' => $subtotal,
            'subtotal_formatted' => format_money($subtotal),
            'total' => $total,
            'total_formatted' => format_money($total),
            'drawer_html' => view('storefront.partials.cart-drawer-content', [
                'cartItems' => $items,
                'cartCount' => $count,
                'cartSubtotal' => $subtotal,
                'deliveryFee' => $deliveryFee,
                'discount' => $discount,
                'branches' => \App\Models\Branch::active()->get(),
            ])->render(),
            'cart_page_html' => view('storefront.cart.partials.content', [
                'items' => $items,
                'subtotal' => $subtotal,
                'branches' => \App\Models\Branch::active()->get(),
            ])->render(),
        ], Response::HTTP_OK);
    }
}
