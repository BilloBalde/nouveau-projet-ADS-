<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $senderEmail;
    public $senderName;
    public $messageContent;
    public $sentAt;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $senderEmail, $senderName, $messageContent)
    {
        $this->subject = $subject;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
        $this->messageContent = $messageContent;
        $this->sentAt = now();
    }

    public function build()
    {
        return $this->subject($this->subject ?: 'Nouveau message de contact - EDAAG TRADING')
                    ->view('mails.contact')
                    ->with([
                        'senderName' => $this->senderName,
                        'senderEmail' => $this->senderEmail,
                        'messageContent' => $this->messageContent,
                        'sentAt' => $this->sentAt,
                        'year' => date('Y')
                    ]);
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject ?: 'Contact Mail',
            replyTo: $this->senderEmail,
            from: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'mails.contact',
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