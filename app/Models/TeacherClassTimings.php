<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherClassTimings extends Model
{
    use HasFactory;
    protected $fillable = [
        'classID',
        'teacherID',
        'courseID',
        'preferred_time_from',
        'preferred_time_to',
    ];
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
    public function class()
    {
        return $this->belongsTo(Course::class, 'classID');
    }
}
