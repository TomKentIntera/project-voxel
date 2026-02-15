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
use App\Libraries\Helpers\PterodactylHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;





class StripeHelper {

    public static function getClient() {
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET')
        );

        return $stripe;
    }

    public static function getProductByID($id) {
        $stripe = StripeHelper::getClient();

        return $stripe->products->retrieve(
            $id,
            []
          );
    }

    public static function getProductPriceByID($id) {
        $stripe = StripeHelper::getClient();

        return $stripe->prices->retrieve(
            $id,
            []
          );
    }

    public static function getProductPriceByCurrency($productId, $currency = 'USD') {
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

    

    public static function getCoupon($coupon) {
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


    
    public static function hasValidBillablePaymentMethod(User $customer) {
        $stripe = StripeHelper::getClient();
        $customer = StripeHelper::getOrCreateCustomer(Auth::user());
        $sources = $stripe->customers->allPaymentMethods(
            $customer->id,
            ['type' => 'card', 'limit' => 3]
          );

        return count($sources->data) > 0 ? true : false;
    }

    public static function getCheckoutSession($sessionID) {
        $stripe = StripeHelper::getClient();
        
        return $stripe->checkout->sessions->retrieve(
            $sessionID,
            []
          );
    }

    public static function processWebhook($webhookData) {
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
                    } else {
                        Log::info('Server already initialised...');
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
