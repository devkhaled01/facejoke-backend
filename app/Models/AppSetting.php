<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';
    protected $fillable = [
        'android_min_version',
        'ios_min_version',
        'is_app_working',
        'down_message',
        'android_store_url',
        'ios_store_url',
        'extra',
    ];
    protected $casts = [
        'is_app_working' => 'boolean',
        'extra' => 'array',
    ];
} 