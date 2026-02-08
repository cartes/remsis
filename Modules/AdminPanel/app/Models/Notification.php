<?php

namespace Modules\AdminPanel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'due_date',
        'is_read',
        'is_active',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_read' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get unread notifications
     */
    public static function unread()
    {
        return self::where('is_read', false)
            ->where('is_active', true)
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get upcoming notifications (within 7 days)
     */
    public static function upcoming()
    {
        return self::where('is_read', false)
            ->where('is_active', true)
            ->where('due_date', '>=', Carbon::today())
            ->where('due_date', '<=', Carbon::today()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Check if notification is urgent (within 3 days)
     */
    public function isUrgent()
    {
        return $this->due_date && $this->due_date->diffInDays(Carbon::today()) <= 3;
    }
}
