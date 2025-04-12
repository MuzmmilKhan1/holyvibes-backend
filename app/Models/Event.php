<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'isPaid',
        'price',
        'time',
        'link',
    ];
    protected $casts = [
        'isPaid' => 'boolean',
        'time' => 'datetime',
        'price' => 'float',
    ];
}
