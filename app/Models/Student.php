<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date_of_birth',
        'guardian_name',
        'email',
        'contact_number',
        'alternate_contact_number',
        'preferred_language',
        'signature',
        'registration_date',
    ];

    // Relationships
    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'studentId');
    }

    public function classEnrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'studentId');
    }
}
