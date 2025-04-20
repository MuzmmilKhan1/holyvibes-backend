<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBilling extends Model
{
    protected $table = 'event_billing';
    protected $fillable = [
        'studentID',
        'eventID',
        'receipt',
        'paymentMethod',
    ];
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'studentID');
    }
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'eventID');
    }
}
