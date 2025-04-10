<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;
    protected $table = "teachers";
    protected $fillable = [
        'name',
        'date_of_birth',
        'gender',
        'nationality',
        'contact_number',
        'email',
        'current_address',
        'experience_Quran',
        'other_experience',
        'languages_spoken',
        'age_group',
        'qualification',
        'institution',
        'application_date',
        'status',
    ];
    public function classTimings()
    {
        return $this->hasMany(ClassTimings::class,foreignKey:'teacherID');
    }
    public function courses()
    {
        return $this->hasMany(Course::class,foreignKey:'teacherID');
    }
}
