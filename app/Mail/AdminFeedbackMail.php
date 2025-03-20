<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class AdminFeedbackMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public $feedback)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Customer Feedback Submission',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-feedback',
            with: [
                'feedback' => $this->feedback,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if (empty($this->feedback->file_path)) {
            return [];
        }

        // Try looking directly in public disk without additional 'public/' prefix
        if (Storage::disk('public')->exists($this->feedback->file_path)) {
            $fullPath = Storage::disk('public')->path($this->feedback->file_path);
            $filename = basename($this->feedback->file_path);
            $mimeType = Storage::disk('public')->mimeType($this->feedback->file_path);
            
            return [
                Attachment::fromPath($fullPath)
                    ->as($filename)
                    ->withMime($mimeType)
            ];
        }
        
        return [];
    }
}