<?php

namespace App\Services\Log;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SlackNotification;


class Service
{
    public function log($message, $context = []) {
        if(env('APP_DEBUG')) {
            Log::info($message, $context);
        }
    }

    public function logToSlack($message, $context) {
          
        Notification::route('slack', '#store-errors')
        ->notify(new SlackNotification($message, $context));

        Log::info("Logging to slack: ".$message.' '. $context);
    }
}