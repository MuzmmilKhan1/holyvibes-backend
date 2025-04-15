<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseTeacher extends Model
{
    protected $table = 'course_teacher';

    protected $fillable = [
        'teacherID',
        'courseID',
    ];
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
}
