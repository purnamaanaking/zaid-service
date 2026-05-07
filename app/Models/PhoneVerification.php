<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVerification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_phone_id',
        'otp_code_hash',
        'channel',
        'status',
        'expires_at',
        'verified_at',
        'attempt_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempt_count' => 'integer',
        ];
    }

    public function userPhone(): BelongsTo
    {
        return $this->belongsTo(UserPhone::class);
    }
}
