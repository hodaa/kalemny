<?php

namespace App\Models;

use Database\Factories\CallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'channel_name',
    'caller_id',
    'recipient_id',
    'status',
    'started_at',
    'answered_at',
    'ended_at',
    'duration_seconds',
    'ended_by_user_id',
    'failure_reason',
])]
class Call extends Model
{
    public const string STATUS_RINGING = 'ringing';

    public const string STATUS_ACCEPTED = 'accepted';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_DECLINED = 'declined';

    public const string STATUS_MISSED = 'missed';

    public const string STATUS_CANCELLED = 'cancelled';

    public const string STATUS_FAILED = 'failed';

    /** @use HasFactory<CallFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'answered_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }

    public function involves(User $user): bool
    {
        return $this->caller_id === $user->id || $this->recipient_id === $user->id;
    }
}
