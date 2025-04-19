<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendence extends Model
{
    use HasFactory;

    protected $table = 'attendences';

    protected $fillable = [
        'classID',
        'studentID',
        'date',
        'status',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'classID');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID');
    }
}
