<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpAttempt extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_phone_id',
        'phone_verification_id',
        'attempt_type',
        'status',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function userPhone(): BelongsTo
    {
        return $this->belongsTo(UserPhone::class);
    }

    public function phoneVerification(): BelongsTo
    {
        return $this->belongsTo(PhoneVerification::class);
    }
}
