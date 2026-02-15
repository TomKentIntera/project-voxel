<?php

namespace App\Services\Stripe;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Str;
use Auth;
use Session;

use App\Models\ReferralCode;
use App\Models\User;
use App\Models\Server;

use App\Services\Log\Service as LogService;
use Carbon\Carbon;
use App\Libraries\Helpers\PterodactylHelper;

class Service
{
    
    private LogService $logger;

    public function __construct(LogService $logger) {
        $this->logger = $logger;
    }
    

    public function getClient() {
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET')
        );

        return $stripe;
    }

    public function getProductByID($id) {
        $stripe = StripeHelper::getClient();

        return $stripe->products->retrieve(
            $id,
            []
          );
    }

    public function getProductPriceByID($id) {
        $stripe = StripeHelper::getClient();

        return $stripe->prices->retrieve(
            $id,
            []
          );
    }

    public function getProductPriceByCurrency($productId, $currency = 'USD') {
        $stripe = StripeHelper::getClient();

        $prices =  $stripe->prices->search([
            'query' => 'product:\''.$productId.'\' AND currency:\''.$currency.'\''
        ]);

        if(count($prices->data) == 0) {
            return StripeHelper::getProductByID($productId)->default_price;
        }
        //dd($prices->data[0]);

        return $prices->data[0]->id;
    }

    

    public function getCoupon($coupon) {
        $stripe = StripeHelper::getClient();

        try {
            $data = $stripe->promotionCodes->all(['code' => $coupon, 'limit' => 1]);
            if(count($data->data) == 0) {
                return null;
            } 

            return $data->data[0];
        } catch (\Throwable $th) {
            return null;
        }
        
    }

    public function getOrCreateCoupon($discountNumber, $discountType = "percent") {
        $client = $this->getClient();
        $existingCoupons = $client->coupons->all(['limit' => 100]);

        $foundCoupon = null;

        foreach ($existingCoupons as $coupon) {
            if(
                $coupon->duration == "once" &&
                $coupon->percent_off == $discountNumber
            ) {
                $foundCoupon = $coupon;
            }
        }

        if($foundCoupon == null) {
            $foundCoupon = $client->coupons->create([
                'percent_off' => floatval($discountNumber),
                'name' => $discountNumber.'% First Month Referral Discount',
                'duration' => 'once',
            ]);
        }

        return $foundCoupon;
    }

    public function createPromoCode($code, $couponId) {
        $stripe = $this->getClient();
        return $stripe->promotionCodes->create([
            'coupon' => $couponId,
            'code' => $code
          ]);
    }

    public function getPromoCodeByCode($code) {
        $stripe = $this->getClient();
        $promos = $stripe->promotionCodes->all(['code' => $code, 'limit' => 1]);

        if(count($promos->data) == 0) {
            return null;
        }

        return $promos->data[0];
    }


    
    public function hasValidBillablePaymentMethod(User $customer) {
        $stripe = StripeHelper::getClient();
        $customer = StripeHelper::getOrCreateCustomer(Auth::user());
        $sources = $stripe->customers->allPaymentMethods(
            $customer->id,
            ['type' => 'card', 'limit' => 3]
          );

        return count($sources->data) > 0 ? true : false;
    }

    public function getCheckoutSession($sessionID) {
        $stripe = StripeHelper::getClient();
        
        return $stripe->checkout->sessions->retrieve(
            $sessionID,
            []
          );
    }

    public function processWebhook($webhookData) {
        $webhookEventType = isset($webhookData["type"]) ? $webhookData["type"] : null;
        $stripe = StripeHelper::getClient();
        
        log::info($webhookEventType);
        switch ($webhookEventType) {
            case 'charge.succeeded':

                $invoiceID = $webhookData['data']['object']['invoice'];
                // get the invoice
                $invoice = $stripe->invoices->retrieve(
                    $invoiceID,
                    []
                );

                // check its status
                $paid = $invoice->paid;
                $subscription = $invoice->subscription;
                log::info('Invoice subscription: '.$subscription);

                if($paid) {
                    $server = Server::where('stripe_tx_id', $subscription)->first();
                    log::info($server);

                    if($server != null) {
                        if($server->stripe_tx_return == false) {
                            // mark it as returned
                            $server->stripe_tx_return = true;
                            $server->save();

                            // trigger intialisation
                            PterodactylHelper::initialiseServer($server);
                        }
                    }
                }

                break;

            case 'customer.subscription.deleted':
                $subscription = $webhookData['data']['object']['id'];
                log::info($subscription);

                
                $server = Server::where('stripe_tx_id', $subscription)->first();
                
                if($server != null) {
                    
                    // trigger intialisation
                    PterodactylHelper::suspendServer($server);
                
                } else {
                    log::info("unable to find server");

                }

                break;
        }
        log::info('Webhook complete');


    }
    

    
}

