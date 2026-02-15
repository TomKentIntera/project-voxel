@if(config('store.showBanner'))
<div class="call-to-action cta-thin cta-red">
    <div class="custom-width">
        <div class="row">
            <div class="col-sm-12">
                <p>LIMITED OFFER: Save 25% on your first month with coupon code <span class="coupon">{{config('store.promoCode')}}</span> at checkout.</p>
            </div>
            
        </div>
    </div>
</div>
@endif