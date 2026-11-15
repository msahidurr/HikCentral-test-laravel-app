<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_data' => 'array',
        'event_time' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
