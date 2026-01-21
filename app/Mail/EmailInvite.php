<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvite extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $inviterName;
    public $companyName;
    public $loginUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        // Assuming relationship: $user->tenant exists
        $this->companyName = $user->tenant->company_name ?? 'Our Organization';
        $this->inviterName = auth()->user()->name ?? 'Administrator';
        // Point to your login route
        $this->loginUrl = route('login'); 
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->companyName} on SyneriaBooks",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invite', // Points to resources/views/emails/user-invited.blade.php
        );
    }

    public function attachments(): array
    {
        return [];
    }
}