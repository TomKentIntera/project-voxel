<?php

namespace App\Services\Price;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Facades\Facade as LaravelFacade;


class Facade extends LaravelFacade
{
    protected static function getFacadeAccessor()
    {
        return 'price';
    }
}