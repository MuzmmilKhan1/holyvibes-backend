<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassEnrollment extends Model
{
    use HasFactory;

    protected $table = 'class_enrollment';

    protected $fillable = [
        'studentId',
        'classId',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'studentId');
    }
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'classId'); // assuming the model name is Classes
    }
}
