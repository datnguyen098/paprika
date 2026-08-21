<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Branch;
use App\Models\Contact;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(Request $request): View
    {
        $branches = Branch::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedBranch = $branches->first(fn (Branch $branch): bool => $branch->slug === $request->query('branch') || (string) $branch->id === (string) $request->query('branch'));

        $seo = SeoService::page(
            is_english() ? 'Contact Paprika | Vietnamese cuisine in Patras' : 'Liên hệ Paprika',
            is_english() ? 'Find Paprika opening hours, hotline and contact form for Vietnamese dining in Patras.' : 'Thông tin liên hệ, địa chỉ, giờ mở cửa và form gửi tin nhắn cho Paprika.',
            is_english() ? 'contact Paprika Patras, Vietnamese restaurant phone' : 'liên hệ Paprika, nhà hàng Việt Nam Patras, số điện thoại Paprika',
            localized_route('contact')
        );

        $schemas = [
            SeoService::restaurantSchema(),
        ];

        return view('contact', compact('seo', 'schemas', 'branches', 'selectedBranch'));
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        Contact::create($request->validated());

        return redirect()
            ->to(localized_route('contact'))
            ->with('success', is_english() ? 'Thank you for contacting us. Paprika will respond as soon as possible.' : 'Cảm ơn bạn đã liên hệ. Paprika sẽ phản hồi trong thời gian sớm nhất.');
    }
}
