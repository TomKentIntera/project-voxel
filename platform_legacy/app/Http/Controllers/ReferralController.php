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
use App\Models\Server;
use App\Models\AvailabilityNotification;
use App\Events\AvailabilityNotificationCreated;
use App\Services\Price\Facade as PriceFacade;

class ReferralController extends BaseController
{
    
    public function getReferrals() {
        $user = Auth::user();
        return view('referrals.home')->with('user', $user);
    }

    public function getInvite($code) {
        Session::put('referralCode', $code);

        return redirect('plans');
    }

}
