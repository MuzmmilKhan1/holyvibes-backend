<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    use HasFactory;
    protected $table = 'course_enrollment';
    protected $fillable = [
        'studentId',
        'courseId',
    ];
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseId');
    }
}
