<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Mail\CustomerReservationReceivedMail;
use App\Mail\NewReservationNotificationMail;
use App\Models\Branch;
use App\Models\Reservation;
use App\Support\ReservationTableAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request): RedirectResponse
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
            'source' => 'web',
        ]));

        $this->queueReservationEmails($reservation->loadMissing('branch', 'table'));

        return redirect()
            ->to(localized_route('reservations.create'))
            ->with('success', __('site.reservation.success'));
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
            Log::warning('Unable to queue reservation notification emails.', [
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
}
