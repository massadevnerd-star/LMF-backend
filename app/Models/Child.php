<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Child extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'nickname',
        'birthdate',
        'type', // 'Adulto', 'Figlio', 'Figlia'
        'avatar'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stories()
    {
        return $this->belongsToMany(Story::class, 'child_story')->withTimestamps();
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'nickname', 'birthdate', 'type', 'avatar'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Child profile {$eventName}");
    }
}
