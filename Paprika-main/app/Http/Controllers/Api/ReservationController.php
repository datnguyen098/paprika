<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Mail\CustomerReservationReceivedMail;
use App\Mail\NewReservationNotificationMail;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Support\OpenDays;
use App\Support\ReservationTableAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReservationController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:190'],
        ], [
            'query.required' => 'Vui lòng nhập email hoặc số điện thoại.',
        ]);

        $queryRaw = trim((string) $data['query']);
        $queryLower = mb_strtolower($queryRaw);
        $digits = preg_replace('/\D+/', '', $queryRaw);

        $reservations = Reservation::query()
            ->with(['branch', 'table'])
            ->where(function ($sub) use ($queryLower, $digits): void {
                if (filter_var($queryLower, FILTER_VALIDATE_EMAIL)) {
                    $sub->whereRaw('LOWER(email) = ?', [$queryLower]);
                }

                if ($digits) {
                    $sub->orWhere('phone', 'like', '%'.$digits.'%');
                    $sub->orWhereRaw($this->phoneDigitsExpression('phone').' like ?', ['%'.$digits.'%']);
                }
            })
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => $reservations->isEmpty()
                ? 'Không tìm thấy yêu cầu đặt bàn phù hợp.'
                : 'Đã tìm thấy '.$reservations->count().' yêu cầu đặt bàn.',
            'data' => $reservations->map(fn (Reservation $reservation): array => $this->transformReservation($reservation))->values(),
        ]);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->loadMissing(['branch', 'table']);

        return response()->json([
            'success' => true,
            'data' => $this->transformReservation($reservation),
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'reservation_date' => ['required', 'date_format:Y-m-d'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:40'],
        ]);

        $branch = Branch::query()->active()->findOrFail($data['branch_id']);
        $durationMinutes = (int) setting('reservation_duration_minutes', ReservationTableAvailability::DEFAULT_DURATION_MINUTES);

        if (! OpenDays::isOpenOn($data['reservation_date'], $branch)) {
            return response()->json([
                'success' => true,
                'message' => 'Quán không nhận đặt bàn vào ngày đã chọn.',
                'data' => [
                    'available' => false,
                    'best_table_id' => null,
                    'tables' => $this->tablesForClosedDay($branch),
                ],
            ]);
        }

        $availableTables = ReservationTableAvailability::availableTables(
            $branch->id,
            $data['reservation_date'],
            $data['reservation_time'],
            (int) $data['guests'],
            null,
            $durationMinutes,
        );
        $availableIds = $availableTables->pluck('id');
        $bestTable = ReservationTableAvailability::bestAvailableTable(
            $branch->id,
            $data['reservation_date'],
            $data['reservation_time'],
            (int) $data['guests'],
            null,
            $durationMinutes,
        );

        $tables = RestaurantTable::query()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (RestaurantTable $table): array => [
                'id' => $table->id,
                'code' => $table->code,
                'name' => $table->name,
                'seats' => $table->seats,
                'zone' => $table->zone,
                'status' => $table->status,
                'available' => $availableIds->contains($table->id),
                'reason' => $table->status !== 'active'
                    ? $table->statusLabel()
                    : ($table->seats < (int) $data['guests'] ? 'Không đủ ghế' : ($availableIds->contains($table->id) ? null : 'Đã có khách đặt')),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => $bestTable ? 'Còn bàn phù hợp.' : 'Không còn bàn phù hợp trong khung giờ này.',
            'data' => [
                'available' => (bool) $bestTable,
                'best_table_id' => $bestTable?->id,
                'tables' => $tables,
            ],
        ]);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tableId = $data['table_id'] ?? null;
        $durationMinutes = (int) setting('reservation_duration_minutes', ReservationTableAvailability::DEFAULT_DURATION_MINUTES);

        if (! $tableId) {
            $tableId = ReservationTableAvailability::bestAvailableTable(
                (int) $data['branch_id'],
                $data['reservation_date'],
                $data['reservation_time'],
                (int) $data['guests'],
                null,
                $durationMinutes,
            )?->id;
        }

        $reservation = Reservation::create(array_merge($data, [
            'table_id' => $tableId,
            'duration_minutes' => $durationMinutes,
            'hold_expires_at' => ReservationTableAvailability::holdExpiresAt($data['reservation_date'], $data['reservation_time']),
            'status' => 'pending',
            'source' => 'flutter',
        ]));

        $reservation->loadMissing('branch', 'table');
        $this->queueReservationEmails($reservation);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu đặt bàn. Nhà hàng sẽ gọi điện xác nhận.',
            'data' => $this->transformReservation($reservation),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'name' => $reservation->name,
            'phone' => $reservation->phone,
            'email' => $reservation->email,
            'status' => $reservation->status,
            'status_label' => $reservation->statusLabel(),
            'branch' => $reservation->branch ? [
                'id' => $reservation->branch->id,
                'name' => $reservation->branch->name,
                'address' => $reservation->branch->address,
                'phone' => $reservation->branch->phone,
            ] : null,
            'table' => $reservation->table ? [
                'id' => $reservation->table->id,
                'name' => $reservation->table->name,
                'seats' => $reservation->table->seats,
                'zone' => $reservation->table->zone,
            ] : null,
            'reservation_date' => $reservation->reservation_date?->toDateString(),
            'reservation_time' => substr((string) $reservation->reservation_time, 0, 5),
            'guests' => $reservation->guests,
            'note' => $reservation->note,
            'created_at' => $reservation->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tablesForClosedDay(Branch $branch): array
    {
        return RestaurantTable::query()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (RestaurantTable $table): array => [
                'id' => $table->id,
                'code' => $table->code,
                'name' => $table->name,
                'seats' => $table->seats,
                'zone' => $table->zone,
                'status' => $table->status,
                'available' => false,
                'reason' => 'Ngày đóng cửa',
            ])
            ->values()
            ->all();
    }

    private function queueReservationEmails(Reservation $reservation): void
    {
        try {
            if ($reservation->email && filter_var($reservation->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($reservation->email)->queue(new CustomerReservationReceivedMail($reservation));
            }

            $adminEmail = $this->adminNotificationEmail($reservation->branch);

            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new NewReservationNotificationMail($reservation));
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to queue API reservation notification emails.', [
                'reservation_id' => $reservation->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function adminNotificationEmail(?Branch $branch): ?string
    {
        foreach ([
            $branch?->order_notification_email,
            $branch?->email,
            setting('order_notification_email'),
        ] as $email) {
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }

    private function phoneDigitsExpression(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ' ', ''), '+', ''), '-', ''), '(', ''), ')', ''), '.', '')";
    }
}
