<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'google_calendar_connection_id',
    'calendar_event_id',
    'google_event_id',
    'google_ical_uid',
    'etag',
    'sync_hash',
    'google_updated_at',
    'last_pushed_at',
    'last_pulled_at',
    'status',
    'metadata',
])]
class GoogleCalendarEventMapping extends Model
{
    protected function casts(): array
    {
        return [
            'google_updated_at' => 'datetime',
            'last_pushed_at' => 'datetime',
            'last_pulled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(GoogleCalendarConnection::class, 'google_calendar_connection_id');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }
}
