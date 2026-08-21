<?php

namespace App\Mail;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReservationNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        $branchName = $this->reservation->branch?->name ?: 'Paprika';
        $dateTime = Carbon::parse($this->reservation->reservation_date)->format('d/m/Y').' '.$this->reservation->reservation_time;

        return new Envelope(
            subject: "[Đặt bàn mới] {$branchName} - {$dateTime} - {$this->reservation->guests} khách",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-reservation',
        );
    }
}
