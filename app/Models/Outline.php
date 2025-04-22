<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outline extends Model
{
    use HasFactory;

    protected $fillable = [
        'courseID',
        'title',
        'description',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseID');
    }
}
