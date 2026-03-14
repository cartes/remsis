<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BcchCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'last_daily_sync_attempted_for',
        'last_daily_sync_attempted_at',
        'last_daily_sync_succeeded_at',
        'last_daily_sync_error',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'username' => 'encrypted',
            'password' => 'encrypted',
            'last_daily_sync_attempted_for' => 'immutable_date',
            'last_daily_sync_attempted_at' => 'immutable_datetime',
            'last_daily_sync_succeeded_at' => 'immutable_datetime',
        ];
    }
}
