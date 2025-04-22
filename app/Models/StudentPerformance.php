<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'classID',
        'courseID',
        'studentID',
        'teacherID',
        'classID',
        'attendance',
        'test_remarks',
        'participation',
        'suggestions',
    ];
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID');
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'classID');
    }
}
