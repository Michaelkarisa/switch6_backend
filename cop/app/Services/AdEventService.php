<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\AdEvent;

class AdEventService
{
    public function log(array $data)
    {
        return AdEvent::create($data);
    }
}