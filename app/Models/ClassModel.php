<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';
    
    protected $fillable = [
        'teacherID',
        'title',
        'description',
        'classLink',
        'total_seats',
        'filled_seats',
    ];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'class_course', 'classID', 'courseID')
                    ->withTimestamps();
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }

    public function studentClassTimings()
    {
        return $this->hasMany(StudentClassTimings::class, 'classID');
    }

    public function teacherClassTimings()
    {
        return $this->hasMany(TeacherClassTimings::class, 'classID');
    }
}