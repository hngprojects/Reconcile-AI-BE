<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutboundMarketingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->data->full_name}, YOU'RE JUST A CLICK AWAY FROM SAVING MONEY AND TIME.",
        );
    }


    public function content(): Content
    {
        return new Content(
            view: 'emails.outbound-marketing',
            with: [
                'fullName' => $this->data->full_name,
                'businessName' => $this->data->business_name,
                'email' => $this->data->email,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
