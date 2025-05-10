<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ClassCreatedMail extends Mailable
{
    public $class;
    public $teacherName;
    public function __construct($class, $teacherName)
    {
        $this->class = $class;
        $this->teacherName = $teacherName;
    }

    public function build()
    {
        return $this->subject('New Class Created - HolyVibes')
            ->view('class-created');
    }
}
