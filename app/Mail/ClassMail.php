<?php

namespace App\Mail;
use Illuminate\Mail\Mailable;

class ClassMail extends Mailable
{
    public $title;
    public $subtitle;
    public $body;

    public function __construct($title, $subtitle, $body)
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->body = $body;
    }

    public function build()
    {
        return $this->view('class') 
            ->subject($this->title)
            ->with([
                'title' => $this->title,
                'message' => $this->subtitle,
                'body' => $this->body,
            ]);
    }
}
