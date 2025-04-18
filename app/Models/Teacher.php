<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;
    protected $table = "teachers";
    protected $fillable = [
        'teach_id',
        'name',
        'date_of_birth',
        'gender',
        'nationality',
        'contact_number',
        'email',
        'current_address',
        'class_course_schedule',
        'experience_Quran',
        'other_experience',
        'languages_spoken',
        'age_group',
        'qualification',
        'institution',
        'application_date',
        'status',
        'class_timings',
    ];
    protected $casts = [
        'class_course_schedule' => 'array',
    ];
    public function classTimings()
    {
        return $this->hasMany(TeacherClassTimings::class, foreignKey: 'teacherID');
    }
    public function courses()
    {
        return $this->hasMany(Course::class, foreignKey: 'teacherID');
    }
}
