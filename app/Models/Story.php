<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Story extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'story_subject',
        'story_type',
        'age_group',
        'image_style',
        'creation_mode',
        'output',
        'cover_image',
        'status'
    ];

    protected $casts = [
        'output' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function children()
    {
        return $this->belongsToMany(Child::class, 'child_story')->withTimestamps();
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'story_subject', 'story_type', 'age_group', 'image_style', 'status', 'cover_image'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Story {$eventName}");
    }
}
