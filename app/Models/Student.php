<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'std_id',
        'name',
        'date_of_birth',
        'guardian_name',
        'email',
        'contact_number',
        'alternate_contact_number',
        'preferred_language',
        'signature',
        'registration_date',
        'class_course_data',
        'status'
    ];

    // Relationships
    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'studentId');
    }

    public function classEnrollments()
    {
        return $this->hasMany(Enrollment::class, 'studentId');
    }
    public function billing()
    {
        return $this->hasMany(Billing::class, 'studentID');
    }
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'studentID');
    }

}
