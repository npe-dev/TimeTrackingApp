<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSetting extends Model
{
    protected $fillable = [
        'enabled',
        'frequency',
        'day_of_week',
        'time',
        'sections',
        'recipient_email',
        'last_run_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'day_of_week' => 'integer',
        'sections' => 'array',
        'last_run_at' => 'datetime',
    ];

    /** All report sections, used as the default when none are stored. */
    public const ALL_SECTIONS = ['critical', 'columns', 'time', 'completed'];

    /**
     * Return the singleton settings row, creating it with defaults on first use.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'enabled' => false,
            'frequency' => 'daily',
            'time' => '09:00',
            'sections' => self::ALL_SECTIONS,
        ]);
    }
}
