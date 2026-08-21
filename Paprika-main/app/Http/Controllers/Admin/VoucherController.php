<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Branch;
use App\Models\Voucher;
use App\Support\TranslationPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $vouchers = Voucher::query()
            ->with('branch')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('code', 'like', '%'.$request->q.'%')
                        ->orWhere('name', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->when($request->filled('public'), fn ($query) => $query->where('is_public', $request->public === '1'))
            ->when($request->filled('default'), fn ($query) => $query->where('is_default', $request->default === '1'))
            ->when($request->filled('type'), fn ($query) => $query->where('discount_type', $request->type))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.vouchers.index', [
            'vouchers' => $vouchers,
            'branches' => $this->branches(),
            'types' => Voucher::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.vouchers.create', [
            'voucher' => new Voucher([
                'discount_type' => Voucher::TYPE_PERCENT,
                'discount_value' => 1000,
                'is_active' => true,
                'is_public' => true,
                'is_default' => false,
                'sort_order' => 0,
            ]),
            'branches' => $this->branches(),
        ]);
    }

    public function store(VoucherRequest $request): RedirectResponse
    {
        $voucher = Voucher::create($this->normalizedData($request));
        $this->syncTranslations($request, $voucher);

        return redirect()->route('admin.vouchers.index')->with('success', 'Da them voucher.');
    }

    public function edit(Voucher $voucher): View
    {
        $voucher->load('translations');

        return view('admin.vouchers.edit', [
            'voucher' => $voucher,
            'branches' => $this->branches(),
        ]);
    }

    public function update(VoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $voucher->update($this->normalizedData($request));
        $this->syncTranslations($request, $voucher);

        return redirect()->route('admin.vouchers.index')->with('success', 'Da cap nhat voucher.');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return back()->with('success', 'Da xoa voucher.');
    }

    private function branches()
    {
        return Branch::query()->active()->orderBy('sort_order')->orderBy('name')->get();
    }

    private function normalizedData(VoucherRequest $request): array
    {
        $data = collect($request->validated())
            ->except(['translations'])
            ->merge([
                'branch_id' => $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
                'discount_value' => $this->discountValue($request),
                'max_discount_amount' => $request->filled('max_discount_amount') ? $this->moneyToMinorUnits($request->input('max_discount_amount')) : null,
                'min_order_amount' => $request->filled('min_order_amount') ? $this->moneyToMinorUnits($request->input('min_order_amount')) : 0,
                'is_active' => $request->boolean('is_active'),
                'is_public' => $request->boolean('is_public'),
                'is_default' => $request->boolean('is_default'),
                'sort_order' => (int) ($request->input('sort_order') ?: 0),
            ])
            ->all();

        if ($data['discount_type'] !== Voucher::TYPE_PERCENT) {
            $data['max_discount_amount'] = null;
        }

        if ($data['discount_type'] === Voucher::TYPE_FREE_SHIPPING) {
            $data['discount_value'] = 0;
        }

        return $data;
    }

    private function discountValue(VoucherRequest $request): int
    {
        if ($request->input('discount_type') === Voucher::TYPE_PERCENT) {
            return (int) round(((float) str_replace(',', '.', (string) $request->input('discount_value'))) * 100);
        }

        return $this->moneyToMinorUnits($request->input('discount_value'));
    }

    private function moneyToMinorUnits(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }

    private function syncTranslations(VoucherRequest $request, Voucher $voucher): void
    {
        $translations = data_get($request->validated(), 'translations', []);

        foreach ($translations as $locale => $fields) {
            if ($locale === config('locales.default')) {
                continue;
            }

            $values = TranslationPayload::prepare($voucher, $locale, $fields);

            if ($values === null) {
                $voucher->translations()->where('locale', $locale)->delete();

                continue;
            }

            $voucher->translations()->updateOrCreate(['locale' => $locale], $values);
        }
    }
}
