<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'teacherID',
        'name',
        'description',
        'price',
        'image',
        'course_duration',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }
    public function classTimings()
    {
        return $this->hasMany(ClassTimings::class,foreignKey:'courseID');
    }
}
