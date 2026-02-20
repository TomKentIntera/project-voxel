<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/dashboard', function () {
    return redirect('client');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/client/billing', 'App\Http\Controllers\StoreController@getBillingArea');
    Route::get('/client', 'App\Http\Controllers\StoreController@getClientArea')->name('home');
    

    Route::get('/plan/purchase/{planUUID}', 'App\Http\Controllers\StoreController@getPurchasePlan');
    Route::get('/plan/configure/{plan}', 'App\Http\Controllers\StoreController@getConfigurePlan');
    Route::post('/plan/configure/{plan}/do', 'App\Http\Controllers\StoreController@getDoConfigurePlan');
    Route::get('/plan/configure/{plan}/mod/{id}', 'App\Http\Controllers\StoreController@getConfigureModdedPlan');
    Route::post('/plan/modded/configure/{plan}/do', 'App\Http\Controllers\StoreController@getDoConfigureModdedPlan');
    
    Route::get('/server/initialise/{serverUUID}', 'App\Http\Controllers\StoreController@getInitialisePlan');
    

    Route::get('api/server/isInitialised/{uuid}', 'App\Http\Controllers\APIController@getIsServerInitialised');

    // Referrals

    Route::get('client/referrals', 'App\Http\Controllers\ReferralController@getReferrals');

});

require __DIR__.'/auth.php';


Route::get('plans', 'App\Http\Controllers\StoreController@getPlans');
Route::get('currency/{currency}', 'App\Http\Controllers\StoreController@getSwitchCurrency');
Route::get('faqs', 'App\Http\Controllers\StoreController@getFAQs');
Route::get('helpdesk', 'App\Http\Controllers\StoreController@getHelpdesk');
Route::get('terms', 'App\Http\Controllers\StoreController@getTerms');
Route::get('privacy-policy', 'App\Http\Controllers\StoreController@getPrivacy');
Route::post('availability', 'App\Http\Controllers\StoreController@getDoCreateNotification');
Route::get('availability/done', 'App\Http\Controllers\StoreController@getNotificationCreateComplete');
Route::get('invite/{code}', 'App\Http\Controllers\ReferralController@getInvite');

// Specific plans
Route::get('vaulthunters', 'App\Http\Controllers\StoreController@getVaultHunters');




Route::get('panel', function () {
    return redirect(env('PTERO_PANEL'));
});


// API routes
Route::get('api/versions/vanilla', 'App\Http\Controllers\APIController@getMinecraftVersions');
Route::get('api/versions/bungee', 'App\Http\Controllers\APIController@getBungeeCordVersions');
Route::get('api/versions/forge', 'App\Http\Controllers\APIController@getForgeVersions');

Route::post('api/webhook/stripe', 'App\Http\Controllers\APIController@getStripeWebhook');


// Testing
Route::get('testing/locations', 'App\Http\Controllers\TestController@getLocations');


Route::get('/', function () {
    return view('home');
});
