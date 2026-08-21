<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReservationsExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationActivity;
use App\Models\RestaurantTable;
use App\Support\BranchAccess;
use App\Support\OpeningHours;
use App\Support\ReservationTableAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $dateFilter = $this->dateFilter($request);
        $selectedDate = $dateFilter['date'];
        $fromDate = $dateFilter['from'];
        $toDate = $dateFilter['to'];
        $status = $request->input('status');

        $reservations = $this->filteredReservations($request, $dateFilter, $status)
            ->limit(200)
            ->get();

        $sections = $this->sections($reservations);
        $todayStats = $this->todayStats($request);
        $branches = $this->branches($request);
        $tables = $this->tables($request);

        return view('admin.reservations.index', compact(
            'reservations',
            'sections',
            'branches',
            'tables',
            'selectedDate',
            'fromDate',
            'toDate',
            'dateFilter',
            'status',
            'todayStats',
        ));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $dateFilter = $this->dateFilter($request);
        $reservations = $this->filteredReservations($request, $dateFilter, $request->input('status'))->get();

        return Excel::download(new ReservationsExport($reservations), 'dat-ban-'.business_now()->format('Ymd-His').'.xlsx');
    }

    public function create(Request $request): View
    {
        $branches = $this->branches($request);
        $branch = $branches->first();
        $tables = $this->tables($request, $branch?->id);

        return view('admin.reservations.create', [
            'reservation' => new Reservation([
                'branch_id' => $branch?->id,
                'reservation_date' => business_today($branch)->toDateString(),
                'reservation_time' => OpeningHours::fromBranch($branch)->firstBookableTime(),
                'guests' => 2,
                'duration_minutes' => (int) setting('reservation_duration_minutes', ReservationTableAvailability::DEFAULT_DURATION_MINUTES),
                'status' => 'confirmed',
                'source' => 'admin',
            ]),
            'branches' => $branches,
            'tables' => $tables,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->reservationData($request);
        BranchAccess::authorize($request->user(), (int) $data['branch_id']);

        $table = $this->resolveTable($data);
        $data['table_id'] = $table?->id;
        $data['hold_expires_at'] = ReservationTableAvailability::holdExpiresAt($data['reservation_date'], $data['reservation_time']);
        $data['source'] = 'admin';
        $data['confirmed_at'] = in_array($data['status'], ['confirmed', 'seated', 'completed'], true) ? now() : null;
        $data['seated_at'] = in_array($data['status'], ['seated', 'completed'], true) ? now() : null;
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        $data['no_show_at'] = $data['status'] === 'no_show' ? now() : null;
        $data['cancelled_at'] = $data['status'] === 'cancelled' ? now() : null;

        $reservation = Reservation::create($data);
        $this->logActivity($reservation, $request, 'created', null, $reservation->status, $data['admin_note'] ?? null);

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Đã tạo đặt bàn.');
    }

    public function show(Reservation $reservation): View
    {
        BranchAccess::authorize(auth()->user(), $reservation->branch_id);
        $reservation->load(['branch', 'table', 'activities.user']);
        $branches = $this->branches(request());
        $tables = $this->tables(request(), $reservation->branch_id);

        return view('admin.reservations.show', compact('reservation', 'branches', 'tables'));
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        BranchAccess::authorize($request->user(), $reservation->branch_id);

        $data = $request->validate([
            'workflow_action' => ['nullable', Rule::in(['contact_attempt', 'confirmed', 'completed', 'cancelled'])],
            'status' => ['required_without:workflow_action', Rule::in(Reservation::STATUSES)],
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:160'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'table_id' => ['nullable', Rule::exists('restaurant_tables', 'id')],
            'reservation_date' => ['nullable', 'date_format:Y-m-d'],
            'reservation_time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $fromStatus = $reservation->status;

        if (! empty($data['workflow_action'])) {
            $this->applyWorkflowAction($reservation, $request, $data['workflow_action'], $data['admin_note'] ?? null);
        } else {
            $this->applyManualUpdate($reservation, $request, $data, $fromStatus);
        }

        return back()->with('success', 'Đã cập nhật đặt bàn.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        BranchAccess::authorize(auth()->user(), $reservation->branch_id);

        $reservation->delete();

        return redirect()->route('admin.reservations.index')->with('success', 'Đã xóa đặt bàn.');
    }

    private function filteredReservations(Request $request, array $dateFilter, ?string $status): Builder
    {
        return BranchAccess::apply(Reservation::query()->with(['branch', 'table']), $request->user())
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('phone', 'like', '%'.$request->q.'%');
                });
            })
            ->when($status && $status !== 'active', fn ($query) => $query->where('status', $status))
            ->when(! $status && ! $request->filled('q'), fn ($query) => $query->whereIn('status', Reservation::ACTIVE_STATUSES))
            ->when($status === 'active', fn ($query) => $query->whereIn('status', Reservation::ACTIVE_STATUSES))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
            ->when($request->filled('table_id'), fn ($query) => $query->where('table_id', $request->table_id))
            ->when($dateFilter['date'], fn ($query) => $query->whereDate('reservation_date', $dateFilter['date']))
            ->when(! $dateFilter['date'] && $dateFilter['from'], fn ($query) => $query->whereDate('reservation_date', '>=', $dateFilter['from']))
            ->when(! $dateFilter['date'] && $dateFilter['to'], fn ($query) => $query->whereDate('reservation_date', '<=', $dateFilter['to']))
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->orderBy('created_at');
    }

    private function dateFilter(Request $request): array
    {
        if ($request->filled('date')) {
            $date = $request->date('date')->toDateString();

            return ['mode' => 'date', 'date' => $date, 'from' => null, 'to' => null];
        }

        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? $request->date('from')->toDateString() : null;
            $to = $request->filled('to') ? $request->date('to')->toDateString() : null;

            if ($from && $to && $from > $to) {
                [$from, $to] = [$to, $from];
            }

            return ['mode' => 'range', 'date' => null, 'from' => $from, 'to' => $to];
        }

        if ($request->filled('q')) {
            return ['mode' => 'search', 'date' => null, 'from' => null, 'to' => null];
        }

        return [
            'mode' => 'upcoming',
            'date' => null,
            'from' => business_today()->toDateString(),
            'to' => business_today()->addDays(7)->toDateString(),
        ];
    }

    private function sections(Collection $reservations): array
    {
        $grouped = $reservations->groupBy(fn (Reservation $reservation): string => $this->sectionKey($reservation));

        return [
            [
                'key' => 'urgent',
                'title' => 'Cần gọi ngay',
                'hint' => 'Đơn chờ quá 30 phút hoặc gần đến giờ dùng bữa.',
                'tone' => 'danger',
                'items' => $grouped->get('urgent', collect()),
            ],
            [
                'key' => 'pending',
                'title' => 'Chờ gọi xác nhận',
                'hint' => 'Đơn mới cần liên hệ khách trước khi giữ bàn.',
                'tone' => 'warning',
                'items' => $grouped->get('pending', collect()),
            ],
            [
                'key' => 'soon',
                'title' => 'Sắp đến giờ',
                'hint' => 'Bàn đã giữ trong 90 phút tới.',
                'tone' => 'info',
                'items' => $grouped->get('soon', collect()),
            ],
            [
                'key' => 'confirmed',
                'title' => 'Đã giữ bàn',
                'hint' => 'Theo dõi khách đến và đánh dấu hoàn tất khi kết thúc phục vụ.',
                'tone' => 'success',
                'items' => $grouped->get('confirmed', collect()),
            ],
            [
                'key' => 'past',
                'title' => 'Đã qua giờ',
                'hint' => 'Đơn đã quá giờ đặt nhưng chưa được chốt hoàn tất hoặc hủy.',
                'tone' => 'danger',
                'items' => $grouped->get('past', collect()),
            ],
            [
                'key' => 'closed',
                'title' => 'Đã kết thúc',
                'hint' => 'Các đơn đã hoàn tất, hủy hoặc no-show trong bộ lọc hiện tại.',
                'tone' => 'muted',
                'items' => $grouped->get('closed', collect()),
            ],
        ];
    }

    private function sectionKey(Reservation $reservation): string
    {
        if (in_array($reservation->status, Reservation::CLOSED_STATUSES, true)) {
            return 'closed';
        }

        if ($reservation->status === 'pending') {
            return $reservation->needsUrgentCall() ? 'urgent' : 'pending';
        }

        if ($reservation->isPastServiceTime()) {
            return 'past';
        }

        if ($reservation->isDueSoon()) {
            return 'soon';
        }

        return in_array($reservation->status, ['confirmed', 'seated'], true) ? 'confirmed' : 'closed';
    }

    private function todayStats(Request $request): array
    {
        $todayReservations = BranchAccess::apply(Reservation::query(), $request->user())
            ->whereDate('reservation_date', business_today()->toDateString())
            ->get();

        return [
            'total' => $todayReservations->count(),
            'pending' => $todayReservations->where('status', 'pending')->count(),
            'confirmed' => $todayReservations->where('status', 'confirmed')->count(),
            'seated' => $todayReservations->where('status', 'seated')->count(),
            'urgent' => $todayReservations->filter(fn (Reservation $reservation): bool => $reservation->needsUrgentCall())->count(),
            'due_soon' => $todayReservations->filter(fn (Reservation $reservation): bool => $reservation->isDueSoon())->count(),
            'past' => $todayReservations->filter(fn (Reservation $reservation): bool => $reservation->isPastServiceTime())->count(),
        ];
    }

    private function applyWorkflowAction(Reservation $reservation, Request $request, string $action, ?string $note): void
    {
        $fromStatus = $reservation->status;
        $updates = [];

        if ($note !== null) {
            $updates['admin_note'] = $note;
        }

        $updates = array_merge($updates, match ($action) {
            'contact_attempt' => [
                'last_contacted_at' => now(),
                'contact_attempts' => min(255, (int) $reservation->contact_attempts + 1),
            ],
            'confirmed' => [
                'status' => 'confirmed',
                'confirmed_at' => $reservation->confirmed_at ?: now(),
                'hold_expires_at' => $reservation->hold_expires_at ?: ReservationTableAvailability::holdExpiresAt($reservation->reservation_date->toDateString(), substr((string) $reservation->reservation_time, 0, 5)),
                'last_contacted_at' => $reservation->last_contacted_at ?: now(),
                'contact_attempts' => max(1, (int) $reservation->contact_attempts),
            ],
            'cancelled' => [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ],
            'completed' => [
                'status' => 'completed',
                'completed_at' => now(),
            ],
        });

        $reservation->update($updates);
        $this->logActivity($reservation, $request, $action, $fromStatus, $reservation->status, $note);
    }

    private function applyManualUpdate(Reservation $reservation, Request $request, array $data, string $fromStatus): void
    {
        $updates = [
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
        ];

        foreach (['name', 'phone', 'email', 'branch_id', 'reservation_date', 'reservation_time', 'duration_minutes', 'guests', 'note'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        $candidate = array_merge($reservation->only(['branch_id', 'reservation_date', 'reservation_time', 'duration_minutes', 'guests']), $updates);
        if (array_key_exists('table_id', $data)) {
            $candidate['table_id'] = $data['table_id'];
        }

        $table = $this->resolveTable($candidate, $reservation->id);
        $updates['table_id'] = $table?->id;
        $updates['hold_expires_at'] = ReservationTableAvailability::holdExpiresAt($candidate['reservation_date'], $candidate['reservation_time']);

        if ($fromStatus !== $data['status']) {
            $updates = array_merge($updates, match ($data['status']) {
                'confirmed' => ['confirmed_at' => $reservation->confirmed_at ?: now()],
                'seated' => ['seated_at' => $reservation->seated_at ?: now()],
                'completed' => ['completed_at' => now()],
                'no_show' => ['no_show_at' => now()],
                'cancelled' => ['cancelled_at' => now()],
                default => [],
            });
        }

        $reservation->update($updates);
        $this->logActivity($reservation, $request, 'updated', $fromStatus, $reservation->status, $data['admin_note'] ?? null);
    }

    private function resolveTable(array $data, ?int $excludeReservationId = null): ?RestaurantTable
    {
        $branchId = (int) $data['branch_id'];
        $date = is_string($data['reservation_date']) ? $data['reservation_date'] : $data['reservation_date']->toDateString();
        $time = substr((string) $data['reservation_time'], 0, 5);
        $guests = (int) $data['guests'];

        if (! empty($data['table_id'])) {
            $table = RestaurantTable::query()->findOrFail($data['table_id']);
            if ((int) $table->branch_id !== $branchId) {
                throw ValidationException::withMessages([
                    'table_id' => 'Bàn đã chọn không thuộc cơ sở này.',
                ]);
            }

            if (! ReservationTableAvailability::isTableAvailable($table, $date, $time, $guests, $excludeReservationId, (int) ($data['duration_minutes'] ?? ReservationTableAvailability::DEFAULT_DURATION_MINUTES))) {
                throw ValidationException::withMessages([
                    'table_id' => 'Bàn đã chọn không còn trống hoặc không đủ số ghế.',
                ]);
            }

            return $table;
        }

        $table = ReservationTableAvailability::bestAvailableTable($branchId, $date, $time, $guests, $excludeReservationId, (int) ($data['duration_minutes'] ?? ReservationTableAvailability::DEFAULT_DURATION_MINUTES));
        if (! $table) {
            throw ValidationException::withMessages([
                'table_id' => 'Hiện không còn bàn phù hợp trong khung giờ này.',
            ]);
        }

        return $table;
    }

    private function reservationData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s().]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:160'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'table_id' => ['nullable', Rule::exists('restaurant_tables', 'id')],
            'reservation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$this->businessTodayForRequest($request)],
            'reservation_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'guests' => ['required', 'integer', 'min:1', 'max:40'],
            'status' => ['required', Rule::in(Reservation::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function logActivity(Reservation $reservation, Request $request, string $action, ?string $fromStatus, ?string $toStatus, ?string $note): void
    {
        ReservationActivity::create([
            'reservation_id' => $reservation->id,
            'user_id' => $request->user()?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function businessTodayForRequest(Request $request): string
    {
        $branch = Branch::query()->active()->find($request->input('branch_id'));

        return business_today($branch)->toDateString();
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

    private function tables(Request $request, ?int $branchId = null)
    {
        return BranchAccess::apply(RestaurantTable::query()->with('branch'), $request->user())
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->active()
            ->orderBy('branch_id')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }
}
