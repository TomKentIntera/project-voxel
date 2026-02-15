<?php

namespace App\Services\Plan;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;


class Service
{
    public function getPlanFromPlanName($name) {
        $plan = null;
        foreach(Config::get('plans.planList') as $p) {
            if($p['name'] === $name) {
                $plan = $p;
            }
        }

        return $plan;
    }

    public function getPlanFromTitle($name) {
        $plan = null;
        foreach(Config::get('plans.planList') as $p) {
            if($p['title'] === $name) {
                $plan = $p;
            }
        }

        return $plan;
    }
}