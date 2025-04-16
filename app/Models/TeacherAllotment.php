<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAllotment extends Model
{
    use HasFactory;

    protected $table = 'teacher_allotment';

    protected $fillable = [
        'courseID',
        'teacherID',
        'studentID',
    ];

    // Relationships

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID');
    }
    public function studentClassTimings()
    {
        return $this->hasMany(StudentClassTimings::class, 'allotmentID');
    }
}
