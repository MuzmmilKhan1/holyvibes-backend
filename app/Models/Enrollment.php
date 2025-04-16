<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'enrollment';

    protected $fillable = [
        'studentId',
        'classId',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentId');
    }
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'classId'); 
    }
}
