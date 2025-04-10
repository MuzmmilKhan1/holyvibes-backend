<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTimings extends Model
{
    use HasFactory;
    protected $fillable = [
        'classID',
        'teacherID',
        'preferred_time_from',
        'preferred_time_to',
        
    ];
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }
}
