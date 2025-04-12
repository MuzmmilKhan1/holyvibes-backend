<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;
    protected $table = 'classes';
    protected $fillable = [
        'courseID',
        'teacherID',
        'title',
        'description',
        'classLink',
        'total_seats',
        'filled_seats',
    ];
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID');
    }  public function classTimings()
    {
        return $this->hasMany(ClassTimings::class, 'classID');
    }
}
