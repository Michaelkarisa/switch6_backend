<?php

namespace App\Models;

class Setting extends BaseAppendOnlyModel
{

protected $fillable=[
    'match_notifications',
    'lineup_auto_save',
    'theme',
];

protected $casts=[
    'match_notifications' => 'boolean',
    'lineup_auto_save' => 'boolean'
];
}