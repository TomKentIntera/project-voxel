<?php

namespace App\Services\Region;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;


class Service
{
    public function getRegionFromCode($code) {
        $regions = Config::get('plans.locations');

        return $regions[$code] ?? null;
    }

    public function getRegionFromPteroCode($pterocode) {
        $regions = Config::get('plans.locations');
        $region = null;

        foreach($regions as $code => $r) {
            if($r['ptero_location'] === $pterocode) {
                $r['code'] = $code;
                $region = $r;
            }
        }

        return $region;
    }

    private function log($message, $context = []) {
        if(env('APP_DEBUG')) {
            Log::info($message, $context);
        }
    }
}