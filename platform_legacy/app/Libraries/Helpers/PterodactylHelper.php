<?php

namespace App\Libraries\Helpers;


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

use App\Services\Log\Service as LogService;
use Illuminate\Support\Facades\App;



class PterodactylHelper {

    public static function api() {
        return env('PTERO_API');
    }

    public static function panel() {
        return rtrim((string) env('PTERO_PANEL', ''), '/');
    }

    public static function apiKey() {
        return env('PTERO_API_KEY');
    }

    public static function defaultHeaders() {
        return [
            'Authorization' => 'Bearer '.PterodactylHelper::apiKey(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    public static function getServerByPteroID($id) {
        if($id == null || $id === '') {
            return null;
        }

        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/servers/'.$id);
        if(!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    public static function getServerByExternalID($externalID) {
        if($externalID == null || $externalID === '') {
            return null;
        }

        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/servers', [
            'filter' => [
                'external_id' => $externalID,
            ],
        ]);

        if(!$response->successful()) {
            return null;
        }

        $serverResults = $response->json('data', []);
        foreach($serverResults as $serverResult) {
            if(
                isset($serverResult['attributes']['external_id']) &&
                $serverResult['attributes']['external_id'] === $externalID
            ) {
                return $serverResult;
            }
        }

        return null;
    }

    public static function getPanelURLForServer(Server $server) {
        $panelURL = PterodactylHelper::panel();
        if($panelURL == '') {
            return null;
        }

        $panelServer = PterodactylHelper::getServerByPteroID($server->ptero_id);
        if($panelServer == null) {
            $panelServer = PterodactylHelper::getServerByExternalID($server->uuid);

            if($panelServer != null && isset($panelServer['attributes']['id'])) {
                $server->ptero_id = strval($panelServer['attributes']['id']);
                $server->save();
            }
        }

        if($panelServer != null && isset($panelServer['attributes']['identifier'])) {
            return $panelURL.'/server/'.$panelServer['attributes']['identifier'];
        }

        return $panelURL;
    }

    public static function getLocations($includeNodes = false) {
        // get location data from the API
        $url = PterodactylHelper::api().'/application/locations';

        if($includeNodes) {
            $url .= '?include=nodes';
        }
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get($url);

        //dd($response->json());
        return $response->json();

    }

    public static function getLocation($id) {
        // get location data from the API
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/locations/'.$id);

        //dd($response->json());
        return $response->json();

    }

    public static function getLocationByName($name) {
        $url = PterodactylHelper::api().'/application/locations';
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get($url);
        $locationFromAPI = null;

        foreach ($response->json()['data'] as $location) {
            if($location['attributes']['short'] == $name) {
                $locationFromAPI = $location['attributes'];
            }
        }

        $response2 = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/locations/'.$locationFromAPI['id'].'?include=nodes');

        //dd($response->json());
        return $response2->json();
    }

    public static function getLocationWithNodes($id) {
        // get location data from the API
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/locations/'.$id.'?include=nodes');

        //dd($response->json());
        return $response->json();

    }

    public static function getNodes($includeServers = false) {
        // get location data from the API
        $url = PterodactylHelper::api().'/application/nodes';

        if($includeServers) {
            $url .= '?include=servers';
        }
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get($url);

        //dd($response->json());
        return $response->json();

    }

    public static function getNode($id) {
        // get Node data from the API
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/nodes/'.$id);

        //dd($response->json());
        return $response->json();

    }

    public static function getNodeWithAllocations($id) {
        // get Node data from the API
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/nodes/'.$id.'?include=allocations');

        //dd($response->json());
        return $response->json();

    }

    

    public static function getNodeWithServers($id) {
        // get node data from the API
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/nodes/'.$id.'?include=servers');

        //dd($response->json());
        return $response->json();

    }

    public static function getMinecraftNest() {
        // get node data from the API
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/nests/?include=eggs');

        //dd($response->json());
        $minecraftNest = null;

        foreach ($response->json()['data'] as $nest) {
            if($nest['attributes']['name'] == "Minecraft") {
                $minecraftNest = $nest['attributes'];
            }
        }

        return $minecraftNest;
    }

    public static function mapServerTypeToEggType($type) {
        $map = [
            'vanilla' => 'Vanilla Minecraft',
            'spigot' => 'Spigot',
            'paper' => 'Paper',
            'bukkit' => 'Vanilla Minecraft',
            'forge' => 'Forge Minecraft',
            'bedrock' => 'Vanilla Minecraft',
        ];

        if(!in_array($type, $map)) {
            return 'CurseForge Generic';
        }

        return $map[$type];
    }

    public static function formatEnvironmentVariables($serverData) {
        switch ($serverData->type) {
            case 'vanilla':
                return [
                    'SERVER_JARFILE' => 'server.jar',
                    'VANILLA_VERSION' => $serverData->minecraft_version
                ];
                break;
            
            case 'spigot':
                return [
                    'SERVER_JARFILE' => 'server.jar'
                ];
                break;
            case 'paper':
                return [
                    'SERVER_JARFILE' => 'server.jar'
                ];
                break;
            case 'bukkit':
                return [
                    'SERVER_JARFILE' => 'server.jar'
                ];
                break;
            case 'forge':
                return [
                    'SERVER_JARFILE' => 'server.jar'
                ];
                break;
            case 'bedrock':
                return [
                    'SERVER_JARFILE' => 'server.jar'
                ];
                break;
                
            case 'curseforge':
                return [
                    'PROJECT_ID' => strval($serverData->mod),
                    'VERSION_ID' => strval($serverData->mod_version),
                    'API_KEY' => env('CURSE_API_KEY')
                ];
                break;
            default:
                return [
                    'SERVER_JARFILE' => 'server.jar',

                ];
                break;
        }
    }

    public static function initialiseServer(Server $server) {
        if($server->initialised) {
            return [
                'response' => 'server already initialised'
            ];
        }

        Log::info('Initialising Server: '.$server->uuid);

        // first, we need the user on the panel
        $panelUser = PterodactylHelper::createUserOnPanel($server->user);


        // now we need to parse the server parameters
        $serverData = $server->data;

        // now, find out how much RAM we need
        $ramRequired = $server->plandata['ram'] * 1024;

        // find the location we want
        $locationName = Config::get('plans.locations.'.$serverData->location)['ptero_location'];
        $locationAndNodes = PterodactylHelper::getLocationByName($locationName);

        $validNodeIDWithLowestUtilisation = null;
        $largestFreeRamOnNode = 0;

        foreach($locationAndNodes['attributes']['relationships']['nodes']['data'] as $node) {
            //dd($node);
            $nodeFreeMemory = $node['attributes']['memory'];

            if(isset($node['attributes']['allocated_resources'])) {
                $nodeFreeMemory -= $node['attributes']['allocated_resources']['memory'];
            }

            if($nodeFreeMemory >= $ramRequired) {
                if($nodeFreeMemory > $largestFreeRamOnNode) {
                    $largestFreeRamOnNode = $nodeFreeMemory;
                    $validNodeIDWithLowestUtilisation = $node['attributes']['id'];
                }
            }
        }

        if($validNodeIDWithLowestUtilisation == null) {
            
            PterodactylHelper::logToSlack("Unable to find node with sufficient space when initialising",json_encode($server));
            dd('UNABLE TO FIND NODE WITH SUFFICIENT SPACE');
            return;
        }

        // next, lets get an allocation on this node
        $allocations = PterodactylHelper::getNodeWithAllocations($validNodeIDWithLowestUtilisation);
        $allocationToUse = null;

        foreach($allocations['attributes']['relationships']['allocations']['data'] as $allocationEntry) {
            if($allocationToUse != null) {
                continue;
            }
            if($allocationEntry['attributes']['assigned'] == false) {
                $allocationToUse = $allocationEntry['attributes'];
                //exit;
            }
        }

        if($allocationToUse == null) {
            
            PterodactylHelper::logToSlack("Unable to find allocation when initialising",json_encode($server));
            return;
        }



        $mcNest = PterodactylHelper::getMinecraftNest();
        //dd($serverData);
        $desiredEggName = PterodactylHelper::mapServerTypeToEggType($serverData->type ?? 'curseforge');
        $egg = null;

        foreach($mcNest['relationships']['eggs']['data'] as $nestEgg) {
            if($nestEgg['attributes']['name'] == $desiredEggName) {
                $egg = $nestEgg['attributes'];
            }
        }

        if($egg == null) {
            PterodactylHelper::logToSlack("Unable to find egg when initialising",json_encode($server));
            return;
        }

        //PterodactylHelper::logToSlack("Found Egg",json_encode($egg));

        $dockerImage = $serverData->docker_image ?? $egg['docker_image'];

        //dd($egg);
        //dd($serverData);

        $initialisationData = [
            "name" => $serverData->name,
            "user" => $panelUser['id'],
            "external_id" => $server->uuid,
            "egg" => $egg['id'],
            "docker_image" => $dockerImage,
            "startup" => $egg['startup'],
            "environment" => PterodactylHelper::formatEnvironmentVariables($serverData),
            "limits" => [
                "memory" => $ramRequired,
                "swap" => -1,
                "disk" => 0,
                "io" => 500,
                "cpu" => 0
            ],
            "feature_limits" => [
                "databases" => 0,
                "backups" => 0
            ],
            "allocation" => [
                "default" => $allocationToUse['id']
            ]
            
        ];

        //PterodactylHelper::logToSlack("Finished generating initialisation data",json_encode($initialisationData));
        //dd($initialisationData);

        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->post(PterodactylHelper::api().'/application/servers', $initialisationData);

        $json = $response->json();

        if(!$response->successful() || !isset($json['attributes']['id'])) {
            PterodactylHelper::logToSlack("Unable to create server when initialising", json_encode($json));
            Log::error('Pterodactyl server creation failed', [
                'server_uuid' => $server->uuid,
                'response_status' => $response->status(),
                'response_body' => $json,
            ]);

            return [
                'result' => 'failed',
            ];
        }

        //dd($response->json());
        //PterodactylHelper::logToSlack("Got response from Pterodactyl",json_encode($json));

        $server->ptero_id = strval($json['attributes']['id']);
        $server->initialised = true;
        $server->save();

        return [
            'result' => 'done'
        ];
    }

    public static function suspendServer(Server $server) {
        // try to suspend with the API
        $server->suspended = true;
        $server->save();
        $id = $server->ptero_id;
        if($id == null) {
            return false;
        }

        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->post(PterodactylHelper::api().'/application/servers/'.$id.'/suspend');

        

        return true;
    }

    public static function getUserByID($id, $showServers = false) {
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->get(PterodactylHelper::api().'/application/users/'.$id.($showServers ? '?include=servers' : ''));

        //dd($response->json());
        return $response->json()['attributes'];
    }

    public static function getUser(User $user, $showServers = false) {
        return PterodactylHelper::getUserByID($user->pterodactyl_panel_id, $showServers);
    }

    public static function createUserOnPanel(User $user) {
        // if we already have the pterodactyl user ID, then just return the user.
        if($user->pterodactyl_panel_id != null) {
            return PterodactylHelper::getUserByID($user->pterodactyl_panel_id);
        }

        // If not, lets make them
        $userData = [
            "email" => $user->email,
            "username" => $user->name,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
        ];

        //dd($userData);
        $response = Http::withHeaders(PterodactylHelper::defaultHeaders())->post(PterodactylHelper::api().'/application/users', $userData);

        $json = $response->json();

        //dd($response->body());
        Log::info(json_encode($response->body()));

        if($json == null) {
            return null;
        }

        // set the ID in the DB
        $user->pterodactyl_panel_id = $json['attributes']['id'];
        $user->save();

        return $json['attributes'];
    }

    public static function cacheData() {
        // Create a datastructure to tell the system how much RAM is available on each node/location
        // We need to know:
        // - how much RAM (total) is available at a given location
        // - how much RAM (highest) is available on any node in a location
        // - how much RAM (total) is available on each node
        $output = [
            'locations' => [],
            'nodes' => []
        ];


        // Get all locations & their nodes
        $locations = json_decode(json_encode(PterodactylHelper::getLocations(true)));

        foreach($locations->data as $location) {
            $entry = [
                'id' => $location->attributes->id,
                'short' => $location->attributes->short,
                'long' => $location->attributes->long,
                'nodeCount' => 0,
            ];

            $totalMemory = 0;
            $totalUsedMemory = 0;
            $totalFreeMemory = 0;
            $maxFreeMemory = 0;
            $memoryUsedPercent = 0;
            $memoryUsedFreestNodePercent = 100;

            // also iterate over all of the nodes
            foreach($location->attributes->relationships->nodes->data as $node) {
                $nodeData = $node->attributes;

                $nodeEntry = [
                    'id' => $nodeData->id,
                    'name' => $nodeData->name,
                    'fqdn' => $nodeData->fqdn,
                    'memory' => $nodeData->memory,
                    'location' => $entry['short'],
                ];
                $entry['nodeCount'] += 1;

                // calculate how much memory has been allocated
                if(isset($nodeData->allocated_resources)) {
                    $nodeEntry['memoryAllocated'] = $nodeData->allocated_resources->memory;
                } else {
                    $nodeEntry['memoryAllocated'] = 0;
                }

                $nodeEntry['memoryUsedPercent'] = ($nodeEntry['memoryAllocated'] / $nodeData->memory) * 100;

                // calculate the free memory
                $freeMemory = $nodeEntry['memory'] - $nodeEntry['memoryAllocated'];
                $nodeEntry['memoryFree'] = $freeMemory;

                array_push($output['nodes'], $nodeEntry);

                if($freeMemory > $maxFreeMemory) {
                    $maxFreeMemory = $freeMemory;
                }

                if($memoryUsedFreestNodePercent > $nodeEntry['memoryUsedPercent']) {
                    $memoryUsedFreestNodePercent = $nodeEntry['memoryUsedPercent'];
                }

                $totalMemory += $nodeData->memory;
                $totalFreeMemory += $freeMemory;
                $totalUsedMemory += $nodeEntry['memoryAllocated'];
                $totalMemoryUsedPercent = $totalUsedMemory / $totalMemory * 100;
            }


            // add the data for the location to the output
            $entry['totalMemory'] = $totalMemory;
            $entry['totalUsedMemory'] = $totalUsedMemory;
            $entry['totalFreeMemory'] = $totalFreeMemory;
            $entry['totalMemoryUsedPercent'] = $totalMemoryUsedPercent;
            $entry['maxFreeMemory'] = $maxFreeMemory;
            $entry['memoryUsedFreestNodePercent'] = $memoryUsedFreestNodePercent;

            $entry['totalMemoryGB'] = $totalMemory / 1024;
            $entry['totalUsedMemoryGB'] = $totalUsedMemory / 1024;
            $entry['totalFreeMemoryGB'] = $totalFreeMemory / 1024;

            $entry['maxFreeMemoryGB'] = $maxFreeMemory / 1024;

            array_push($output['locations'], $entry);
        }

        Storage::disk('local')->put('locations.json', json_encode($output, JSON_PRETTY_PRINT));
    }

    public static function logToSlack($message, $context) {
        $logService = App::make(LogService::class);

        $logService->logToSlack($message, $context);
    }

    

    
}
