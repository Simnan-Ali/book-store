<?php

namespace App\Support;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function record(string $action, Model $subject, string $description): void
    {
        Activity::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'description' => $description,
        ]);
    }
}
