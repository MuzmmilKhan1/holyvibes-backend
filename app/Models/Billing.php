<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'billings';

    protected $fillable = [
        'studentID',
        'courseID',
        'receipt',
        'paymentMethod',
        'paymentStatus',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentID');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
    public function billings()
    {
        return $this->hasMany(Billing::class, 'courseID');
    }

}
