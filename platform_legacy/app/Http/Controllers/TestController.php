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
use App\Libraries\Helpers\StripeHelper;
use App\Libraries\Helpers\PterodactylHelper;
use App\Models\Server;

use App\Services\Pterodactyl\Service as PterodactlyService;
use App\Services\ReferralCode\Service as ReferralService;

use App\Services\Price\Facade as Price;

use App\Services\Log\Service as LogService;
use Illuminate\Support\Facades\App;



class TestController extends BaseController
{

    private PterodactlyService $ptero;
    private ReferralService $referralService;

    public function __construct(PterodactlyService $ptero, ReferralService $referralService) {
        $this->ptero = $ptero;
        $this->referralService = $referralService;
    }
    
    public function getLocations() {

        $logService = App::make(LogService::class);
        $server = Server::find(22);
        $message = json_encode($server);

        $logService->logToSlack($message);
        dd('done');
        //dd(Auth::user()->servers);
        //$this->ptero->getAllLocations(true);
        $amount = 100;

        $currencies = array_map(
            function($item) {
                return $item['symbol'];
            },
            Price::getSupportedCurrenciesList()
        );

        $exchanges = [];

        foreach ($currencies as $currency1) {
            foreach ($currencies as $currency2) {
                $exchanges[$currency1.'>'.$currency2] = Price::convertCurrency($amount, $currency1, $currency2);
            }
        }
        dd($exchanges);
    }

}
