<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassCourse extends Model
{
    use HasFactory;

    protected $table = 'class_course';

    protected $fillable = [
        'classID',
        'courseID',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'classID');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }


    public function teacherClassTimings()
    {
        return $this->hasMany(TeacherClassTimings::class, 'classID');
    }


}
