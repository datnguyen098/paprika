<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerPaymentConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $transactionCode = '',
    ) {}

    public function envelope(): Envelope
    {
        $this->locale($this->order->locale ?? 'en');

        return new Envelope(
            subject: __('emails.customer_payment_confirmed.subject', ['code' => $this->order->code]),
        );
    }

    public function content(): Content
    {
        $this->locale($this->order->locale ?? 'en');

        return new Content(
            view: 'emails.customer-payment-confirmed',
        );
    }
}
