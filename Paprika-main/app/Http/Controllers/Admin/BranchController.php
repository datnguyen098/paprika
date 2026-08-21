<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use App\Services\UploadService;
use App\Support\OpenDays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private readonly UploadService $uploads) {}

    public function index(Request $request): View
    {
        $branches = Branch::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('city', 'like', '%'.$request->q.'%')
                        ->orWhere('address', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('admin.branches.create', [
            'branch' => new Branch([
                'is_active' => true,
                'accepts_online_orders' => true,
                'accepts_pickup_orders' => true,
                'accepts_delivery_orders' => true,
                'accepts_offline_payment' => true,
                'auto_delivery_quote_enabled' => false,
                'delivery_min_order_amount' => 1000,
                'delivery_max_distance_km' => 6,
                'reservation_time_slots' => '09:00-14:00,16:00-21:00',
                'reservation_last_booking_time' => '20:30',
                'reservation_last_order_buffer_minutes' => 30,
            ]),
        ]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'branches');
        }

        $branch = Branch::create($data);
        $this->syncDeliveryZones($branch, $request->input('delivery_zones', []));

        return redirect()->route('admin.branches.index')->with('success', 'Đã thêm cơ sở.');
    }

    public function edit(Branch $branch): View
    {
        $branch->load('deliveryZones');

        return view('admin.branches.edit', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('image')) {
            $oldImage = $branch->image;
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'branches');
            $this->uploads->deleteImage($oldImage);
        }

        $branch->update($data);
        $this->syncDeliveryZones($branch, $request->input('delivery_zones', []));

        return redirect()->route('admin.branches.index')->with('success', 'Đã cập nhật cơ sở.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->uploads->deleteImage($branch->image);
        $branch->delete();

        return back()->with('success', 'Đã xóa cơ sở.');
    }

    private function normalizedData(BranchRequest $request): array
    {
        $sortOrderInput = $request->input('sort_order');
        $sortOrder = is_numeric($sortOrderInput)
            ? (int) $sortOrderInput
            : ((int) Branch::query()->max('sort_order')) + 1;

        return array_merge(
            $request->safe()->except(['image', 'delivery_zones', 'open_days']),
            [
                'open_days' => $request->filled('open_days') ? implode(',', OpenDays::normalize($request->input('open_days'))) : null,
                'is_active' => $request->boolean('is_active'),
                'accepts_online_orders' => $request->boolean('accepts_online_orders'),
                'accepts_pickup_orders' => $request->boolean('accepts_pickup_orders'),
                'accepts_delivery_orders' => $request->boolean('accepts_delivery_orders'),
                'accepts_offline_payment' => $request->boolean('accepts_offline_payment'),
                'auto_delivery_quote_enabled' => $request->boolean('auto_delivery_quote_enabled'),
                'delivery_min_order_amount' => $this->moneyToMinorUnits($request->input('delivery_min_order_amount')),
                'delivery_free_order_amount' => $request->filled('delivery_free_order_amount') ? $this->moneyToMinorUnits($request->input('delivery_free_order_amount')) : null,
                'sort_order' => $sortOrder,
            ]
        );
    }

    private function syncDeliveryZones(Branch $branch, array $zones): void
    {
        foreach ($zones as $index => $zoneData) {
            $id = $zoneData['id'] ?? null;

            if (! empty($zoneData['delete'])) {
                if ($id) {
                    $branch->deliveryZones()->whereKey($id)->delete();
                }

                continue;
            }

            $hasDistance = filled($zoneData['min_distance_km'] ?? null) || filled($zoneData['max_distance_km'] ?? null);
            $hasFee = filled($zoneData['fee'] ?? null) && (float) str_replace(',', '.', (string) $zoneData['fee']) > 0;
            $hasLabel = filled($zoneData['label'] ?? null);

            if (! $hasDistance && ! $hasFee && ! $hasLabel) {
                continue;
            }

            $payload = [
                'label' => $zoneData['label'] ?? null,
                'min_distance_km' => (float) ($zoneData['min_distance_km'] ?? 0),
                'max_distance_km' => filled($zoneData['max_distance_km'] ?? null) ? (float) $zoneData['max_distance_km'] : null,
                'fee' => $this->moneyToMinorUnits($zoneData['fee'] ?? 0),
                'sort_order' => is_numeric($zoneData['sort_order'] ?? null) ? (int) $zoneData['sort_order'] : $index,
                'is_active' => (bool) ($zoneData['is_active'] ?? false),
            ];

            if ($id) {
                $branch->deliveryZones()->whereKey($id)->update($payload);
            } else {
                $branch->deliveryZones()->create($payload);
            }
        }
    }

    private function moneyToMinorUnits(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }
}
