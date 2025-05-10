<?php

namespace App\Mail;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentPerformance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentReport extends Mailable
{
    use Queueable, SerializesModels;

    public $student, $course, $class, $performance;

    public function __construct($student, $course, $class, $performance)
    {
        $this->student = $student;
        $this->course = $course;
        $this->class = $class;
        $this->performance = $performance;
    }

    public function build()
    {
        return $this->view('studentreport')
            ->with([
                'student' => $this->student,
                'course' => $this->course,
                'class' => $this->class,
                'performance' => $this->performance
            ]);
    }
}
