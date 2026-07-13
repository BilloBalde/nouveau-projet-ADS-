<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $subject;
    public $body;
    public $userName;
    public $action;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $body, $userName = null, $action = 'changed')
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->userName = $userName;
        $this->action = $action; // 'changed', 'reset', 'created'
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view('mails.passwordmail')
                    ->with([
                        'userName' => $this->userName,
                        'body' => $this->body,
                        'action' => $this->action,
                        'year' => date('Y')
                    ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.passwordmail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}