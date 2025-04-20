<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    protected $table = 'event_participants';
    protected $fillable = [
        'eventID',
        'studentID',
        'is_member',
        'payment_status',
    ];
    protected $casts = [
        'is_member' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class,'eventID');
    }
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'studentID');
    }
}
