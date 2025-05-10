<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $resetUrl;
    public function __construct($user, $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
    }
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset - HolyVibes',
        );
    }
    public function build()
    {
        return $this->view('passwordreset')
            ->with([
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
            ])
            ->withSwiftMessage(function ($message) {
                $filePath = '../public/images/logo.png';
                if (!file_exists($filePath)) {
                    throw new Exception('Image not found at: ' . $filePath);
                }
                $message->embed($filePath, 'logo_cid');
            });
    }
    public function attachments(): array
    {
        return [];
    }
}
