<?php

namespace Tests\Feature;

use App\Exports\ReservationsExport;
use App\Mail\CustomerReservationReceivedMail;
use App\Mail\NewReservationNotificationMail;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationActivity;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReservationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_index_defaults_to_next_seven_days(): void
    {
        Carbon::setTestNow('2026-06-04 10:00:00');

        $admin = $this->admin();
        $branch = $this->branch();
        $insideToday = $this->reservation($branch, 'Default Today', now()->toDateString());
        $insideEnd = $this->reservation($branch, 'Default End', now()->addDays(7)->toDateString());
        $outside = $this->reservation($branch, 'Default Outside', now()->addDays(8)->toDateString());

        $this->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee($insideToday->name)
            ->assertSee($insideEnd->name)
            ->assertDontSee($outside->name)
            ->assertSee('7 ngày tới')
            ->assertSee('Cả hôm nay')
            ->assertSee('Ngày mai');
    }

    public function test_admin_date_range_and_search_filters_follow_spec(): void
    {
        Carbon::setTestNow('2026-06-04 10:00:00');

        $admin = $this->admin();
        $branch = $this->branch();
        $dateMatch = $this->reservation($branch, 'Date Exact Match', '2026-06-10');
        $rangeMatch = $this->reservation($branch, 'Range Match', '2026-06-12');
        $searchOnly = $this->reservation($branch, 'Search Historic Guest', '2026-05-01', ['status' => 'completed']);
        $searchWrongDate = $this->reservation($branch, 'Search Future Guest', '2026-06-15');

        $this->actingAs($admin)
            ->get(route('admin.reservations.index', ['date' => '2026-06-10']))
            ->assertOk()
            ->assertSee($dateMatch->name)
            ->assertDontSee($rangeMatch->name);

        $this->actingAs($admin)
            ->get(route('admin.reservations.index', ['from' => '2026-06-11', 'to' => '2026-06-13']))
            ->assertOk()
            ->assertSee($rangeMatch->name)
            ->assertDontSee($dateMatch->name);

        $this->actingAs($admin)
            ->get(route('admin.reservations.index', ['q' => 'Historic']))
            ->assertOk()
            ->assertSee($searchOnly->name);

        $this->actingAs($admin)
            ->get(route('admin.reservations.index', ['q' => 'Search', 'date' => '2026-06-15']))
            ->assertOk()
            ->assertSee($searchWrongDate->name)
            ->assertDontSee($searchOnly->name);
    }

    public function test_reservation_model_helpers_match_admin_grouping_rules(): void
    {
        $branch = $this->branch();
        Carbon::setTestNow(Carbon::parse('2026-06-04 12:00:00', business_timezone($branch)));

        $waitedTooLong = $this->reservation($branch, 'Waited Too Long', '2026-06-04', [
            'reservation_time' => '16:00',
            'created_at' => business_now($branch)->subMinutes(31),
        ]);
        $dueSoon = $this->reservation($branch, 'Due Soon Pending', '2026-06-04', [
            'reservation_time' => '13:20',
        ]);
        $confirmedSoon = $this->reservation($branch, 'Confirmed Soon', '2026-06-04', [
            'status' => 'confirmed',
            'reservation_time' => '13:10',
        ]);
        $past = $this->reservation($branch, 'Past Active', '2026-06-04', [
            'status' => 'confirmed',
            'reservation_time' => '11:30',
        ]);
        $closed = $this->reservation($branch, 'Closed Reservation', '2026-06-04', [
            'status' => 'cancelled',
            'reservation_time' => '11:30',
        ]);

        $this->assertSame(31, $waitedTooLong->fresh()->waitingMinutes());
        $this->assertTrue($waitedTooLong->fresh()->needsUrgentCall());
        $this->assertTrue($dueSoon->fresh()->needsUrgentCall());
        $this->assertTrue($confirmedSoon->fresh()->isDueSoon());
        $this->assertTrue($past->fresh()->isPastServiceTime());
        $this->assertFalse($closed->fresh()->isPastServiceTime());
        $this->assertSame(0, $confirmedSoon->fresh()->waitingMinutes());
    }

    public function test_workflow_actions_update_status_timestamps_counters_and_activity(): void
    {
        Carbon::setTestNow('2026-06-04 12:00:00');

        $admin = $this->admin();
        $reservation = $this->reservation($this->branch(), 'Workflow Guest', '2026-06-05');

        $this->actingAs($admin)
            ->put(route('admin.reservations.update', $reservation), [
                'workflow_action' => 'contact_attempt',
                'admin_note' => 'Called once',
            ])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame('pending', $reservation->status);
        $this->assertSame(1, $reservation->contact_attempts);
        $this->assertNotNull($reservation->last_contacted_at);
        $this->assertDatabaseHas(ReservationActivity::class, [
            'reservation_id' => $reservation->id,
            'action' => 'contact_attempt',
            'from_status' => 'pending',
            'to_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.reservations.update', $reservation), [
                'workflow_action' => 'confirmed',
            ])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame('confirmed', $reservation->status);
        $this->assertNotNull($reservation->confirmed_at);
        $this->assertSame(1, $reservation->contact_attempts);

        $this->actingAs($admin)
            ->put(route('admin.reservations.update', $reservation), [
                'workflow_action' => 'completed',
            ])
            ->assertRedirect();

        $reservation->refresh();
        $this->assertSame('completed', $reservation->status);
        $this->assertNotNull($reservation->completed_at);
        $this->assertDatabaseHas(ReservationActivity::class, [
            'reservation_id' => $reservation->id,
            'action' => 'completed',
            'from_status' => 'confirmed',
            'to_status' => 'completed',
        ]);
    }

    public function test_storefront_reservation_queues_customer_and_admin_emails_by_priority(): void
    {
        Carbon::setTestNow('2026-06-04 10:00:00');
        Mail::fake();

        $branch = $this->branch();
        $branch->update([
            'order_notification_email' => 'branch-notify@example.com',
            'email' => 'branch-email@example.com',
        ]);
        SiteSetting::set('order_notification_email', 'settings@example.com');

        $this->post(route('localized.vi.reservations.store'), [
            'name' => 'Mail Guest',
            'phone' => '0901234567',
            'email' => 'guest@example.com',
            'branch_id' => $branch->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '18:00',
            'guests' => 2,
            'note' => 'Window table',
        ])
            ->assertRedirect(localized_route('reservations.create'))
            ->assertSessionHas('success');

        $reservation = Reservation::where('email', 'guest@example.com')->firstOrFail();
        $this->assertSame('pending', $reservation->status);
        $this->assertSame('web', $reservation->source);
        $this->assertNotNull($reservation->table_id);

        Mail::assertQueued(CustomerReservationReceivedMail::class, fn (CustomerReservationReceivedMail $mail): bool => $mail->hasTo('guest@example.com'));
        Mail::assertQueued(NewReservationNotificationMail::class, fn (NewReservationNotificationMail $mail): bool => $mail->hasTo('branch-notify@example.com'));
    }

    public function test_storefront_admin_email_falls_back_to_branch_email_then_setting(): void
    {
        Carbon::setTestNow('2026-06-04 10:00:00');
        Mail::fake();

        $branch = $this->branch();
        $branch->update([
            'order_notification_email' => null,
            'email' => 'branch-fallback@example.com',
        ]);
        SiteSetting::set('order_notification_email', 'settings@example.com');

        $this->post(route('localized.vi.reservations.store'), [
            'name' => 'Fallback Branch Guest',
            'phone' => '0901234568',
            'branch_id' => $branch->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '18:00',
            'guests' => 2,
        ])->assertRedirect();

        Mail::assertQueued(NewReservationNotificationMail::class, fn (NewReservationNotificationMail $mail): bool => $mail->hasTo('branch-fallback@example.com'));

        Mail::fake();
        $branch->update(['email' => null]);

        $this->post(route('localized.vi.reservations.store'), [
            'name' => 'Fallback Setting Guest',
            'phone' => '0901234569',
            'branch_id' => $branch->id,
            'reservation_date' => now()->addDays(2)->toDateString(),
            'reservation_time' => '18:00',
            'guests' => 2,
        ])->assertRedirect();

        Mail::assertQueued(NewReservationNotificationMail::class, fn (NewReservationNotificationMail $mail): bool => $mail->hasTo('settings@example.com'));
    }

    public function test_export_route_downloads_filtered_reservations(): void
    {
        Carbon::setTestNow('2026-06-04 10:00:00');
        Excel::fake();

        $admin = $this->admin();
        $branch = $this->branch();
        $included = $this->reservation($branch, 'Export Included', '2026-06-06');
        $this->reservation($branch, 'Export Excluded', '2026-06-12');

        $this->actingAs($admin)
            ->get(route('admin.reservations.export', ['from' => '2026-06-05', 'to' => '2026-06-07']))
            ->assertOk();

        Excel::matchByRegex();
        Excel::assertDownloaded('/dat-ban-\d{8}-\d{6}\.xlsx/', function (ReservationsExport $export) use ($included): bool {
            $rows = $export->array();

            return $rows[0][0] === 'Danh sách đặt bàn'
                && $rows[2][0] === 'Ngày đặt'
                && collect($rows)->flatten()->contains($included->name)
                && ! collect($rows)->flatten()->contains('Export Excluded');
        });
    }

    private function admin(): User
    {
        return User::where('email', 'admin@paprika-patras.gr')->firstOrFail();
    }

    private function branch(): Branch
    {
        return Branch::active()->firstOrFail();
    }

    private function reservation(Branch $branch, string $name, string $date, array $overrides = []): Reservation
    {
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $tableId = $overrides['table_id'] ?? RestaurantTable::query()
            ->where('branch_id', $branch->id)
            ->active()
            ->orderBy('seats')
            ->value('id');

        $reservation = Reservation::create(array_merge([
            'branch_id' => $branch->id,
            'table_id' => $tableId,
            'name' => $name,
            'phone' => '0900000000',
            'email' => null,
            'reservation_date' => $date,
            'reservation_time' => '18:00',
            'duration_minutes' => 90,
            'guests' => 2,
            'status' => 'pending',
            'source' => 'web',
        ], $overrides));

        if ($createdAt) {
            $reservation->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
        }

        return $reservation;
    }
}
