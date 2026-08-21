<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerReservationReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        $restaurantName = localized_setting('restaurant_name', config('app.name', 'Paprika'));

        return new Envelope(
            subject: "[{$restaurantName}] đã nhận yêu cầu đặt bàn của bạn",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-reservation-received',
        );
    }
}
