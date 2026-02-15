<?php

namespace App\Services\Price;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Http;

use App\Services\Log\Service as LogService;

class Service
{

    private LogService $logger;

    public function __construct(LogService $logger) {
        $this->logger = $logger;
        $this->defaultCurrency = 'USD';
    }
    public $defaultCurrency;


    public function getPlanPrice($name, $decimals = 2, $currency = null) {
        $plan = null;
        foreach(Config::get('plans.planList') as $p) {
            if($p['name'] === $name) {
                $plan = $p;
            }
        }

        if($currency == null) {
            $currency = $this->getCurrency();
        }

        if($plan != null && $currency != null) {
            return number_format((float)$plan['displayPrice'][$currency], $decimals, '.', ',');
        }

        return 0;
    }

    public function getStripeProductForPlan($plan) {
        return $plan['stripe_subscription'][env('APP_ENVIRONMENT')];
    }

    public function getCurrency() {
        $currency = null;
        // first, if they're logged in, get their logged in currency
        if (Auth::guest() == false) {
            return $this->validateCurrency(Auth::user()->currency);
        }

        // if not, fall back to their session, if not fall back to their IP
        $currency = Session::get('currency', null);
        if($currency != null) {
            return $this->validateCurrency($currency);
        }

        // if not, fall back to the Location from their IP
        $currency = $this->getCurrencyFromLocation();
        $this->logger->log('Get currency from location: '.$currency);

        //
        
        return $this->validateCurrency($currency);
    }

    public function getCurrencyData($currency = null) {
        if($currency == null) {
            $currency = $this->getCurrency();
        }

        foreach ($this->getSupportedCurrenciesList() as $c) {
            if($c['symbol'] == $currency) {
                return $c;
            }
        }

        return [
            'symbol' => 'USD',
            'currency' => 'US Dollars',
            'flag' => 'us.svg'
        ];
    }

    public function supportedCurrencies() {
        return array_map(function($item) {
            return $item['symbol'];
        }, $this->getSupportedCurrenciesList());
    }

    public function getSupportedCurrenciesList() {
        return [
            [
                'symbol' => 'EUR',
                'currency' => 'Euros (€)',
                'flag' => 'eu.svg'
            ],
            [
                'symbol' => 'GBP',
                'currency' => 'British Pounds (£)',
                'flag' => 'gb.svg'
            ],
            [
                'symbol' => 'USD',
                'currency' => 'US Dollars ($)',
                'flag' => 'us.svg'
            ],
        ];
    }

    private function validateCurrency($currency, $saveToSession = true) {
        if(in_array($currency, $this->supportedCurrencies())) {
            if($saveToSession) {
                Session::put('currency', $currency);
            }
            return $currency;
        } else {
            return $this->defaultCurrency;
        }
    }

    private function getCurrencyFromLocation() {
        if($position = Location::get()) {
            // Successfully retrieved position.
            $country = $position->countryName;
            $this->logger->log('Country: '.$country);
            
            $currency = $this->matchCountryToCurrency($country);

            return $currency;

        } else {
            $this->logger->log('Unable to get currency from GeoIP');
            return null;
        }

    }

    private function matchCountryToCurrency($country) {
        try {
            return match($country) {
                'United States' => 'USD',
                'Austria' => 'EUR',
                'Belgium' => 'EUR',
                'Bulgaria' => 'EUR',
                'Croatia' => 'EUR',
                'Republic of Cyprus' => 'EUR',
                'Czech Republic' => 'EUR',
                'Denmark' => 'EUR',
                'Estonia' => 'EUR',
                'Finland' => 'EUR',
                'France' => 'EUR',
                'Germany' => 'EUR',
                'Greece' => 'EUR',
                'Hungary' => 'EUR',
                'Ireland' => 'EUR',
                'Italy' => 'EUR',
                'Latvia' => 'EUR',
                'Lithuania' => 'EUR',
                'Luxembourg' => 'EUR',
                'Malta' => 'EUR',
                'Netherlands' => 'EUR',
                'Poland' => 'EUR',
                'Portugal' => 'EUR',
                'Romania' => 'EUR',
                'Slovakia' => 'EUR',
                'Slovenia' => 'EUR',
                'Spain' => 'EUR',
                'Sweden' => 'EUR',
                'United Kingdom' => 'GBP'
            };
        } catch (\Throwable $th) {
            $this->logger->log('Error getting country. '.$th->getMessage());
        }
        $this->logger->log('Failed to match currency');
        return null;
    }

    public function getCurrencySymbol() {
        return match($this->getCurrency()) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        };
    }

    public function setCurrency($currency = 'USD') {
        if(Auth::guest()) {
            Session::put($this->validateCurrency($currency));
        } else {
            $user = Auth::user();
            $user->currency = $this->validateCurrency($currency);
            $user->save();
        }
    }

    public function retrieveExchangeRateData($useCachedData = false) {
        $url = env('EXCHANGE_API'). 'latest?access_key='.env('EXCHANGE_API_KEY');

        if($useCachedData) {
            $json = json_decode(Storage::disk('local')->get('exchangerates_raw.json'), true);
        } else {
            $data = Http::get($url);

            $json = $data->json();
           
            Storage::disk('local')->put('exchangerates_raw.json', json_encode($json, JSON_PRETTY_PRINT));
        }
        
        $currencies = array_map(
            function($item) {
                return $item['symbol'];
            },
            $this->getSupportedCurrenciesList()
        );

        $exchangeRateData = [
            'EUR' => []
        ];

        foreach ($currencies as $currency) {
            $exchangeRateData[$currency] = [];
            $exchangeRateData['EUR'][$currency] = $json['rates'][$currency];
        }

        // go over each pair, calculate the exchange rates forward and backwards        
        $exchangeRateData['USD']['USD'] = 1;
        $exchangeRateData['USD']['GBP'] = $json['rates']['GBP'] / $json['rates']['USD'];
        $exchangeRateData['USD']['EUR'] = 1 / $exchangeRateData['EUR']['USD'];

        $exchangeRateData['GBP']['GBP'] = 1;
        $exchangeRateData['GBP']['EUR'] = 1 / $exchangeRateData['EUR']['GBP'];
        $exchangeRateData['GBP']['USD'] = 1 / $exchangeRateData['USD']['GBP'];
        
        
        Storage::disk('local')->put('exchangerates.json', json_encode($exchangeRateData, JSON_PRETTY_PRINT));

    }

    public function convertCurrency(float $amount, string $fromCurrency = 'USD', string $toCurrency = 'GBP'): float
    {
        if(!Storage::disk('local')->exists('exchangerates.json')) {
            $this->retrieveExchangeRateData();
        }

        $exchangeRateData = json_decode(Storage::disk('local')->get('exchangerates.json'), true);

        return $amount * $exchangeRateData[$fromCurrency][$toCurrency];
    }
}