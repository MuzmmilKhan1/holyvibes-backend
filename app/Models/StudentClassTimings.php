<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClassTimings extends Model
{
    use HasFactory;
    protected $table = 'student_class_timings';

    protected $fillable = [
        'classID',
        'studentID',
        'courseID',
        'allotmentID',
        'preferred_time_from',
        'preferred_time_to',
    ];
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID');
    }
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'classID');
    }

    public function teacherClassTimings()
    {
        return $this->belongsTo(ClassModel::class, 'classID');
    }

    public function teacherAllotment()
    {
        return $this->belongsTo(TeacherAllotment::class, 'allotmentID');
    }
}
