<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewQuoteRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuoteRequest $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[RFQ #{$this->quote->id}] {$this->quote->company} – {$this->quote->items->count()} mã",
            replyTo: [new Address($this->quote->email, $this->quote->contact_name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.new-quote');
    }
}
