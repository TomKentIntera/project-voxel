<?php

namespace App\Services\Pterodactyl;

use Illuminate\Support\Facades\Http;

use Config;
use Auth;
use Session;
use Storage;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// WARNING:
// NOT YET COMPLETE, USE PTERODACTYLHELPER INSTEAD!!!
class Service
{
    private $apiKey;
    private string $apiUri;
    private Pterodactyl $client;

    public function __construct() {
        $this->apiKey = env('PTERO_API_KEY');
        $this->apiUri = env('PTERO_API');
    }

    public function getClient() {
        return $this->client;
    }

    public function api() {
        return $this->apiUri;
    }

    public function defaultHeaders() {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    public function getAllLocations($includeNodes = false) {
        $merged = [];
        $initialLocationSet = $this->getLocations($includeNodes);

        dd($initialLocationSet);
        if(isset($initialLocationSet['meta'])) {

        }

        return $merged;

    }

    public function getLocations($includeNodes = false, $page = 1) {
        // get location data from the API
        $url = $this->api().'/application/locations';

        if($includeNodes) {
            $url .= '?include=nodes';
        }
        if($page > 1) {
            $url .= '?page='.$page;
        }
        $response = Http::withHeaders($this->defaultHeaders())->get($url);

        //dd($response->json());
        return $response->json();

    }


    private function log($message, $context = []) {
        if(env('APP_DEBUG')) {
            Log::info($message, $context);
        }
    }
}