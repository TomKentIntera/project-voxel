<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Session;
use URL;
use Log;
use Illuminate\Support\Facades\Http;
use App\Models\Server;
use Illuminate\Http\Request;
use Str;
use Storage;
use App\Jobs\processStipeWebhookDelay;

class APIController extends BaseController
{
    
    public function getMinecraftVersions() {
        $minecraftVersions = [];

        $manifestLocation = "https://piston-meta.mojang.com/mc/game/version_manifest.json";

        $manifestData = Http::get($manifestLocation);
        $releaseData = $manifestData->json()['versions'];

        foreach ($releaseData as $version) {
            if($version['type'] == 'release') {
                $version['title'] = $version['id'];
                array_push($minecraftVersions, $version);
            }
        }

        return response()->json($minecraftVersions, 200);
    }

    public function getBungeeCordVersions() {
        $minecraftVersions = [];

        // Add the "Latest" version
        array_push($minecraftVersions, ["_class" => "hudson.maven.MavenModuleSetBuild","number" => "latest","url" => "https://ci.md-5.net/job/BungeeCord/lastStableBuild/", 'title' => 'latest']);

        $manifestLocation = "https://ci.md-5.net/job/BungeeCord/api/json";

        $manifestData = Http::get($manifestLocation);
        $releaseData = $manifestData->json()['builds'];

        foreach ($releaseData as $version) {
            $version['title'] = $version['number'];
            array_push($minecraftVersions, $version);
            
        }

        return response()->json($minecraftVersions, 200);
    }

    public function getForgeVersions() {
        $minecraftVersions = [];

        // Add the "Latest" version
        
        $manifestLocation = "https://files.minecraftforge.net/net/minecraftforge/forge/promotions_slim.json";

        $manifestData = Http::get($manifestLocation);
        $releaseData = $manifestData->json()['promos'];

        foreach ($releaseData as $version_string => $forge_version) {
            $minecraftVersions[$version_string] = $forge_version;
            
        }


        return response()->json(array_reverse($minecraftVersions), 200);
    }


    public function getIsServerInitialised($uuid) {
        $server = Server::where('uuid', $uuid)->firstOrFail();

        $serverData = new \StdClass();
        $serverData->initialised = $server->initialised;

        

        return response()->json($serverData, 200);
    }

    public function getStripeWebhook(Request $request) {
        $data = $request->all();
        //$fileName = "transactions_stripe/".$data["occurred_at"]."_".$data["event_type"].".json";
       
        $fileName = 'stripe_webhook/'.Str::random(10).'.json';
        Storage::disk("transaction")->put($fileName, json_encode($data));

        processStipeWebhookDelay::dispatch($data)->delay(now()->addMinutes(1));;

        return response()->json(["result" => "ok"], 200);
    }

    

    
}
