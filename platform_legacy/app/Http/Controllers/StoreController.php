<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;


use Session;
use URL;
use Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Auth;
use Str;
use Log;
use Storage;
use App\Libraries\Helpers\StripeHelper;
use App\Libraries\Helpers\PterodactylHelper;
use App\Models\Server;
use App\Models\AvailabilityNotification;
use App\Events\AvailabilityNotificationCreated;
use App\Services\Price\Facade as PriceFacade;

use App\Services\Stripe\Service as StripeService;

use App\Models\CurseGameVersion;
use App\Models\CurseGameMod;
use App\Models\CurseModVersion;

class StoreController extends BaseController
{

    private $stripeService;

    public function __construct(StripeService $stripeService) {
        $this->stripeService = $stripeService;
    }
    
    public function getHome() {
        return view('home');
    }

    public function getVaultHunters() {
        return view('vaulthunters');
    }

    public function getPlans() {
        return view('plans');
    }

    public function getFAQs() {
        return view('faqs');
    }

    public function getTerms() {
        return view('terms');
    }

    public function getPrivacy() {
        return view('privacy');
    }

    public function getHelpdesk() {
        return redirect('https://interagames.atlassian.net/servicedesk/customer/portals');
    }

    public function getUptime() {
        return redirect('https://hetrixtools.com/r/5bbaaf593de044c059545ba5c8cc16f6/');
    }

    public function getClientArea() {
        $user = Auth::user();

        // Only create/fetch Stripe customer if keys are configured
        if (config('cashier.secret')) {
            $user->createOrGetStripeCustomer();
        }

        return view('clientarea')->with('user', $user);
    }

    public function getServerPanel($serverUUID) {
        $server = Server::where('uuid', $serverUUID)->where('user_id', Auth::id())->firstOrFail();

        if($server->stripe_tx_return == false || $server->initialised == false) {
            return redirect('/client')->with('error', 'Your server is still provisioning. Please try again shortly.');
        }

        $panelURL = PterodactylHelper::getPanelURLForServer($server);
        if($panelURL == null) {
            return redirect('/client')->with('error', 'The panel URL is not configured.');
        }

        return redirect()->away($panelURL);
    }

    public function getBillingArea() {
        $user = Auth::user();

        if (!config('cashier.secret')) {
            return redirect('/client')->with('error', 'Stripe is not configured.');
        }

        return $user->redirectToBillingPortal();
    }

    

    public function getPurchasePlan($serverID) {
        $user = Auth::user();

        $server = Server::where('uuid', $serverID)->firstOrFail();


        $plan = null;
        //dd($server);

        foreach (Config::get('plans.planList') as $planElement) {
            if($planElement['name'] == $server->plan) {
                $plan = $planElement;
            }
        }

        if($plan == null) {
            Log::info("Plan missing");
            return redirect('/plans');
        }

        $subscriptionId = PriceFacade::getStripeProductForPlan($plan);
        $price = StripeHelper::getProductPriceByCurrency($subscriptionId, PriceFacade::getCurrency());

        $checkoutData = [
            'success_url' => url('/server/initialise/'.$serverID.'?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/plans'),
            'subscription_data' => [
                'description' => 'Server: '. $server->data->name
            ],
            'currency' => PriceFacade::getCurrency(),
            'allow_promotion_codes' => true,
            
        ];

        if(Session::has('referralCode')) {
            $promo = $this->stripeService->getPromoCodeByCode(Session::get('referralCode'));
            if($promo != null) {
                $checkoutData['discounts'] = [];
                $checkoutData['discounts'][] = ['promotion_code' => $promo->id];
                unset($checkoutData['allow_promotion_codes']);
            }
        }

        //dd($checkoutData);
        return $user->newSubscription($subscriptionId, $price)->checkout(
            $checkoutData
        );
    }

    public function getConfigurePlan($planName) {
        Session::put('url.intended', URL::full());
        $plan = null;

        foreach (Config::get('plans.planList') as $planEntry) {
            if($planEntry['name'] == $planName) {
                $plan = $planEntry;
            }
        }

        $minecraftVersions = [];

        $mcVersions = CurseGameVersion::where(['gameId' => 1])->orderBy('slug', 'desc')->get();
        
        foreach ($mcVersions as $v) {
            $minecraftVersions[] = ["title" => $v->name, "id" => $v->name];
        }

        // parse location data too
        // this is used to disable locations with insufficient memory
        $locations = [

        ];

        // load a cached copy of the panel info
        $panelLocationData = json_decode(Storage::disk('local')->get('locations.json'));
        $unavailableInSomeLocations = false;

        foreach($plan['locations'] as $locationCode) {
            // get the plan location
            $configLocationInfo = Config::get('plans.locations.'.$locationCode);

            
            // take the plan info, and look through our panel data
            $planRequiredMemory = $plan['ram'] * 1024;
            $planAvailableAtLocation = false;
            $maxFreeMemory = 0;

            foreach ($panelLocationData->locations as $panelLocation) {
                if($panelLocation->short == $configLocationInfo['ptero_location']) {
                    if($panelLocation->maxFreeMemory >= $planRequiredMemory) {
                        $planAvailableAtLocation = true;
                    }

                    $maxFreeMemory = $panelLocation->maxFreeMemory;
                }
            }

            $entry = [
                'locationID' => $locationCode,
                'locationName' => $configLocationInfo['title'],
                'flag' => $configLocationInfo['flag'],
                'requiredMemory' => $planRequiredMemory,
                'maxFreeMemory' => $maxFreeMemory,
                'avilable' => $planAvailableAtLocation
            ];

            array_push($locations, $entry);

            if($planAvailableAtLocation == false) {
                $unavailableInSomeLocations = true;
            }

        }

        //dd($locations);

        return view('orderplan')->with('planName', $planName)->with('plan', $plan)->with('minecraftVersions', $minecraftVersions)->with('locations', $locations)->with('unavailableInSomeLocations', $unavailableInSomeLocations);
    }

    public function getDoConfigurePlan(Request $request) {
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            "plan" => "required",
            "location" => "required",
            "minecraft_version" => "required",
            "type" => "required"
        ]);

        

        $serverConfig = [
            "name" => $request->input('name', 'Server'.Str::random(10)),
            "location" => $request->input('location'),
            "minecraft_version" => $request->input('minecraft_version'),
            "type" => $request->input('type'),
            "type_version" => $request->input('type_version')
        ];

        $server = Server::create([
            'config' => json_encode($serverConfig),
            'plan' => $request->input('plan'),
            'user_id' => Auth::user()->id,
            'uuid' => Str::uuid(),
            'status' => 'new',
        ]);

        return redirect('/plan/purchase/'.$server->uuid);
    }

    public function getConfigureModdedPlan($planName, $modId) {
        Session::put('url.intended', URL::full());
        $plan = null;

        foreach (Config::get('plans.planList') as $planEntry) {
            if($planEntry['name'] == $planName) {
                $plan = $planEntry;
            }
        }

        $modVersions = [];

        $mcVersions = CurseModVersion::where(['modId' => $modId])->orderBy('curseId', 'desc')->get();
        
        foreach ($mcVersions as $v) {
            $modVersions[] = ["curseId" => $v->curseId, "name" => $v->name, "mcversion" => $v->gameVersion];
        }

        // parse location data too
        // this is used to disable locations with insufficient memory
        $locations = [

        ];

        // load a cached copy of the panel info
        $panelLocationData = json_decode(Storage::disk('local')->get('locations.json'));
        $unavailableInSomeLocations = false;

        foreach($plan['locations'] as $locationCode) {
            // get the plan location
            $configLocationInfo = Config::get('plans.locations.'.$locationCode);

            
            // take the plan info, and look through our panel data
            $planRequiredMemory = $plan['ram'] * 1024;
            $planAvailableAtLocation = false;
            $maxFreeMemory = 0;

            foreach ($panelLocationData->locations as $panelLocation) {
                if($panelLocation->short == $configLocationInfo['ptero_location']) {
                    if($panelLocation->maxFreeMemory >= $planRequiredMemory) {
                        $planAvailableAtLocation = true;
                    }

                    $maxFreeMemory = $panelLocation->maxFreeMemory;
                }
            }

            $entry = [
                'locationID' => $locationCode,
                'locationName' => $configLocationInfo['title'],
                'flag' => $configLocationInfo['flag'],
                'requiredMemory' => $planRequiredMemory,
                'maxFreeMemory' => $maxFreeMemory,
                'avilable' => $planAvailableAtLocation
            ];

            array_push($locations, $entry);

            if($planAvailableAtLocation == false) {
                $unavailableInSomeLocations = true;
            }

        }

        //dd($locations);

        return view('orderplan_modded')->with('planName', $planName)->with('modId', $modId)->with('plan', $plan)->with('modVersions', $modVersions)->with('locations', $locations)->with('unavailableInSomeLocations', $unavailableInSomeLocations);
    }

    public function getDoConfigureModdedPlan(Request $request) {
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            "plan" => "required",
            "location" => "required",
            "mod_version" => "required",
            "mod" => "required"
        ]);

        $modData = CurseGameMod::find(intval($request->input('mod')));

        $modConfig = null;
        $dockerImage = null;

        foreach (Config::get('mods') as $modId => $modDataEntry) {
            if(intval($modDataEntry['modId']) === intval($request->input('mod'))) {
                $modConfig = $modDataEntry;
                $dockerImage = $modDataEntry['dockerImage'];
            }
        }

        //dd($modConfig);
      

        $serverConfig = [
            "name" => $request->input('name', 'Server '.Str::random(10)),
            "location" => $request->input('location'),
            "mod_version" => $request->input('mod_version'),
            "mod" => $modData->curseModId,
            'type' => 'curseforge'
        ];

        if($dockerImage != null) {
            $serverConfig['docker_image'] = $dockerImage;
        }

        $server = Server::create([
            'config' => json_encode($serverConfig),
            'plan' => $request->input('plan'),
            'user_id' => Auth::user()->id,
            'uuid' => Str::uuid(),
            'status' => 'new',
        ]);

        return redirect('/plan/purchase/'.$server->uuid);
    }

    public function getInitialisePlan($serverID, Request $request) {

        
        $server = Server::where('uuid', $serverID)->firstOrFail();

        if($request->input('session_id', false)) {
            $session = StripeHelper::getCheckoutSession($request->input('session_id'));
            //dd($session);
            $subscription = $session->subscription;
            $server->stripe_tx_id = $subscription;
            $server->save();

            //dd($server);

        }

        $serverData = new \StdClass();
        $serverData->config = json_decode($server->config);
        $serverData->uuid = $server->uuid;
        $serverData->plan = $server->plan;
        $serverData->planPrice = PriceFacade::getPlanPrice($server->plan);
        $plan = null;

        foreach (Config::get('plans.planList') as $planElement) {
            if($planElement['name'] == $serverData->plan) {
                $plan = $planElement;
            }
        }

        $configLines = [];

        $configLines[] = [
            "icon" => "fa-sign",
            "title" => "Name",
            "value" => $serverData->config->name
        ];
        $configLines[] = [
            "icon" => "fa-map-marked-alt",
            "title" => "Server Location",
            "value" => Config::get('plans.locations.'.$serverData->config->location.'.title')
        ];

        if($serverData->config->type === "curseforge") {
            $mod = CurseGameMod::where('curseModId', intval($serverData->config->mod))->first();
            $configLines[] = [
                "icon" => "fa-toolbox",
                "title" => "Mod",
                "value" => $mod->name ?? 'Unknown'
            ];
            $modVersion = CurseModVersion::where('curseId', intval($serverData->config->mod_version))->first();
            $configLines[] = [
                "icon" => "fa-tachometer-alt",
                "title" => "Mod Version",
                "value" => $modVersion->name ?? 'Latest'
            ];
        } else { 
            $configLines[] = [
                "icon" => "fa-tags",
                "title" => "Minecraft Version",
                "value" => $serverData->config->minecraft_version
            ];
            $configLines[] = [
                "icon" => "fa-toolbox",
                "title" => "Server Type",
                "value" => ucfirst($serverData->config->type)
            ];
            if($serverData->config->type_version != "null") {
                $configLines[] = [
                    "icon" => "fa-tachometer-alt",
                    "title" => "Type Version",
                    "value" => $serverData->config->type_version
                ];
            }
            
        }
        
    

        return view('initialise')->with('serverData', $serverData)->with('serverConfigLines', $configLines)->with('plan', $plan);
    }

    public function getDoCreateNotification(Request $request) {
        //dd($request->all());
        $validator = Validator::make($request->all(), [
            "plan" => "required",
            "email" => "required|email",
            "region" => "required"
        ]);

        if($validator->valid()) {
            AvailabilityNotification::create([
                'email' => $request->input('email'),
                'plan' => $request->input('plan'),
                'region' => $request->input('region')
            ]);
    
            return redirect('/availability/done');
        } else {
            return Redirect::back();
        }
    }

    public function getNotificationCreateComplete() {
        return view('availability_done');
    }

    public function getSwitchCurrency($currency, Request $request) {
        PriceFacade::setCurrency($currency);

        return redirect($request->input('redirect', '/'));
    }
}
