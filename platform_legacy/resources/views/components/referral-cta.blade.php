@if(Auth::user()->referralCodes->count() <= 20)
<div class="call-to-action cta-green cta-bg cta-profile" style="background-image:url({{asset('images/plan-images/skeleton.png')}}); 
    background-size: 140px;
    background-position: right 80px top 30px;">
    <div class="custom-width">
        <div class="row">
            <div class="col-sm-6">
                <h3>Want to earn free credit for your subscription?</h3>
                <p>Intera operates a generous referral bonus system. Simply refer a friend and they'll get {{Config::get('referral.defaultDiscountPercent')}}% off their first month, and you'll get {{Config::get('referral.defaultReferralPercent')}}% of their first 3 month's subscription in credit to your account.</p>
            </div>
            <div class="col-sm-6">
                <div class="buttons">
                    <a href="{{url('client/referrals')}}" class="btn btn-outline btn-outline-green btn-large">Go to referrals <i class="fas fa-long-arrow-alt-right"></i></a>
                    <p class="smalltext">Terms and Conditions Apply</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endif