<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Reservation;
use App\Support\BranchAccess;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = auth()->user();
        $chatUnread = ChatMessage::query()
            ->tap(fn ($query) => BranchAccess::applyViaRelation($query, $user, 'chatSession'))
            ->where('sender', 'visitor')
            ->where('is_read', false)
            ->count();

        $pendingReservations = Reservation::query()
            ->with('branch')
            ->tap(fn ($query) => BranchAccess::apply($query, $user))
            ->where('status', 'pending')
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->orderBy('created_at')
            ->get();
        $reservationPending = $pendingReservations->count();
        $reservationUrgent = $pendingReservations->filter(fn (Reservation $reservation): bool => $reservation->needsUrgentCall())->count();

        $contactNew = Contact::query()
            ->tap(fn ($query) => BranchAccess::apply($query, $user))
            ->where('status', 'new')
            ->count();

        $pendingOrders = BranchAccess::apply(Order::query(), $user)
            ->whereIn('status', ['pending', 'confirmed', 'preparing'])
            ->count();

        $newPaidOrders = BranchAccess::apply(Order::query(), $user)
            ->where('status', 'pending')
            ->where('payment_status', 'paid')
            ->count();

        $latestChats = ChatSession::query()
            ->with('branch')
            ->tap(fn ($query) => BranchAccess::apply($query, $user))
            ->latest('last_message_at')
            ->limit(5)
            ->get()
            ->map(function (ChatSession $chat): array {
                $latestMessage = $chat->messages()->latest()->first();

                return [
                    'type' => 'chat',
                    'title' => $chat->visitor_name ?: 'Khách ghé thăm',
                    'body' => $latestMessage?->message ?: 'Hội thoại mới',
                    'branch' => $chat->branch?->name,
                    'url' => route('admin.chats.show', $chat),
                    'time' => optional($chat->last_message_at)->format('H:i'),
                    'urgent' => false,
                ];
            });

        $latestReservations = $pendingReservations
            ->sortBy(fn (Reservation $reservation): string => ($reservation->needsUrgentCall() ? '0' : '1').'-'.$reservation->scheduledAt()->format('YmdHi'))
            ->take(4)
            ->map(fn (Reservation $reservation): array => [
                'type' => 'reservation',
                'title' => $reservation->name,
                'body' => ($reservation->needsUrgentCall() ? 'Cần gọi ngay - ' : '').'Đặt bàn '.$reservation->reservation_date->format('d/m/Y').' lúc '.substr($reservation->reservation_time, 0, 5),
                'branch' => $reservation->branch?->name,
                'url' => route('admin.reservations.show', $reservation),
                'time' => business_time($reservation->created_at, $reservation->branch)?->format('H:i'),
                'urgent' => $reservation->needsUrgentCall(),
            ]);

        $latestContacts = Contact::query()
            ->with('branch')
            ->tap(fn ($query) => BranchAccess::apply($query, $user))
            ->where('status', 'new')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Contact $contact): array => [
                'type' => 'contact',
                'title' => $contact->name,
                'body' => str($contact->message)->limit(80)->toString(),
                'branch' => $contact->branch?->name,
                'url' => route('admin.contacts.show', $contact),
                'time' => $contact->created_at->format('H:i'),
                'urgent' => false,
            ]);

        $latestOrders = BranchAccess::apply(Order::query(), $user)
            ->whereIn('status', ['pending', 'confirmed', 'preparing'])
            ->with('branch')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Order $order): array => [
                'type' => 'order',
                'title' => $order->code.' — '.$order->customer_name,
                'body' => $order->fulfillment_method === 'delivery' ? 'Giao hàng' : 'Tự nhận',
                'branch' => $order->branch?->name,
                'url' => route('admin.orders.show', $order),
                'time' => business_time($order->created_at, $order->branch)?->format('H:i'),
                'urgent' => $order->status === 'pending' && $order->payment_status === 'paid',
            ]);

        return response()->json([
            'counts' => [
                'chat' => $chatUnread,
                'reservations' => $reservationPending,
                'reservation_urgent' => $reservationUrgent,
                'contacts' => $contactNew,
                'orders' => $pendingOrders,
                'new_paid_orders' => $newPaidOrders,
                'total' => $chatUnread + $reservationPending + $contactNew + $pendingOrders + $newPaidOrders,
            ],
            'items' => $latestOrders
                ->concat($latestReservations)
                ->concat($latestContacts)
                ->concat($latestChats)
                ->take(10)
                ->values(),
        ]);
    }
}
