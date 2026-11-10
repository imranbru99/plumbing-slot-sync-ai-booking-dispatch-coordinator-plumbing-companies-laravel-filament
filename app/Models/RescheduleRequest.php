<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescheduleRequest extends Model
{
    protected $fillable = [
        'appointment_id', 'original_starts_at', 'proposed_starts_at', 'proposed_ends_at',
        'reason', 'status', 'ai_suggestion', 'requested_by', 'approved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'original_starts_at' => 'datetime',
            'proposed_starts_at' => 'datetime',
            'proposed_ends_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
