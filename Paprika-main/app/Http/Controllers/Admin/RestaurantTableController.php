<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RestaurantTable;
use App\Support\BranchAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RestaurantTableController extends Controller
{
    public function index(Request $request): View
    {
        $tables = BranchAccess::apply(RestaurantTable::query()->with('branch'), $request->user())
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('code', 'like', '%'.$request->q.'%')
                        ->orWhere('name', 'like', '%'.$request->q.'%')
                        ->orWhere('zone', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderBy('branch_id')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        $branches = $this->branches($request);

        return view('admin.restaurant-tables.index', compact('tables', 'branches'));
    }

    public function create(Request $request): View
    {
        $branches = $this->branches($request);
        $branch = $branches->first();

        return view('admin.restaurant-tables.create', [
            'table' => new RestaurantTable([
                'branch_id' => $branch?->id,
                'seats' => 2,
                'status' => 'active',
                'sort_order' => ((int) RestaurantTable::query()->max('sort_order')) + 1,
            ]),
            'branches' => $branches,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        BranchAccess::authorize($request->user(), (int) $data['branch_id']);

        RestaurantTable::create($data);

        return redirect()->route('admin.restaurant-tables.index')->with('success', 'Đã thêm bàn.');
    }

    public function edit(RestaurantTable $restaurantTable): View
    {
        BranchAccess::authorize(auth()->user(), $restaurantTable->branch_id);

        return view('admin.restaurant-tables.edit', [
            'table' => $restaurantTable,
            'branches' => $this->branches(request()),
        ]);
    }

    public function update(Request $request, RestaurantTable $restaurantTable): RedirectResponse
    {
        BranchAccess::authorize($request->user(), $restaurantTable->branch_id);
        $data = $this->validateData($request, $restaurantTable);
        BranchAccess::authorize($request->user(), (int) $data['branch_id']);

        $restaurantTable->update($data);

        return redirect()->route('admin.restaurant-tables.index')->with('success', 'Đã cập nhật bàn.');
    }

    public function destroy(RestaurantTable $restaurantTable): RedirectResponse
    {
        BranchAccess::authorize(auth()->user(), $restaurantTable->branch_id);

        if ($restaurantTable->reservations()->exists()) {
            return back()->with('error', 'Không thể xóa bàn đã có lịch đặt. Hãy chuyển trạng thái sang tạm ẩn hoặc bảo trì.');
        }

        $restaurantTable->delete();

        return back()->with('success', 'Đã xóa bàn.');
    }

    private function validateData(Request $request, ?RestaurantTable $table = null): array
    {
        $data = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('restaurant_tables', 'code')
                    ->where(fn ($query) => $query->where('branch_id', $request->input('branch_id')))
                    ->ignore($table?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'seats' => ['required', 'integer', 'min:1', 'max:40'],
            'zone' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(RestaurantTable::STATUSES)],
            'is_joinable' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['is_joinable'] = $request->boolean('is_joinable');
        $data['sort_order'] = (int) ($request->input('sort_order') ?: 0);

        return $data;
    }

    private function branches(Request $request)
    {
        return Branch::query()
            ->active()
            ->when($request->user()?->branch_id, fn ($query) => $query->where('id', $request->user()->branch_id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
