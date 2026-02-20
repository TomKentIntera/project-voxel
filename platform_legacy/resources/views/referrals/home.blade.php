@extends('templates.main')

@section('content')
   
    
    <!-- Layout - Text right  -->
    <div class="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg" style="background-image:url({{asset('images/headers/dark.png')}});">
        <div class="container">
            <div class="row planSelector">
                <div class="col-sm-12">
                    <h2 class="text-center">Your Referrals Account</h2>
                    
                </div>
                
            </div>
        </div>
    </div>
    <!-- Layout - Text right ends here  -->


    <!-- Pricing Tables -->
    <div class="pricing-tables custom-pricing padding-top50 padding-bottom50">
        <div class="custom-width">
            @if($user->referralCodes->count() == 0)
            <div class="call-to-action cta-green cta-bg cta-profile" style="background-image:url({{asset('images/plan-images/skeleton.png')}}); 
            background-size: 140px;
            background-position: right 80px top 30px;">
                <div class="custom-width">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3>How do referrals work?</h3>
                            <p>Simply refer a friend and they'll get {{Config::get('referral.defaultDiscountPercent')}}% off their first month, and you'll get {{Config::get('referral.defaultReferralPercent')}}% of their first 3 month's subscription in credit to your account.</p>
                        </div>
                        <div class="col-sm-6">
                            <div class="buttons">
                                <a href="{{url('client/referrals/generate')}}" class="btn btn-outline btn-outline-green btn-large">Generate Referral Code <i class="fas fa-long-arrow-alt-right"></i></a>
                                <p class="smalltext">Terms and Conditions Apply</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else


            @endif
            
            <div class="row">
                <div class="col-sm-4 col-md-3 mb-40px">
                    <div class="account-nav">
                        
                    </div>
                </div>  
                <div class="col-sm-8 col-md-9 features-six">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="referral-link">
                                <input type="text" disabled="disabled" value="{{ReferralCode::getLatestLink()}}">
                                <button class="copy" data-link="{{ReferralCode::getLatestLink()}}"><i class="fas fa-copy"></i> Copy</button>
                            </div>
                            
                        </div>
                    </div>
                    <div class="row">
                        @forelse($user->servers as $server)
                        @if($server->stripe_tx_return)
                        <div class="col-sm-4">
                            <div class="text-container mb-80" data-server-id="{{$server->id}}">
                                <div class="text">
                                    <div class="img-content">
                                        <i class="fa fa-server {{$server->suspended ? 'text-red' : null}}"></i>
                                    </div>
                                    <h4>{{$server->data->name}}</h4>
                                    <p class="mb-20">{{$server->plandata['title']}} Plan ({{$server->plandata['ram']}} GB)</p>
                                    @if($server->suspended)
                                    <p><a href="#" class="btn btn-light-blue w-100 mb-10" disabled="disabled">Suspended</a></p>
                                    <p><a href="#" class="btn btn-green btn-red btn-disabled w-100" disabled="disabled"><i class="fas fa-times text-white button"></i> Cancel Server</a></p>
                                    
                                    @else
                                    <p><a href="{{route('client.server.panel', ['serverUUID' => $server->uuid])}}" class="btn btn-green w-100 mb-10"><i class="fas fa-share text-white button"></i> Admin Panel</a></p>
                                    <p><a href="{{url('client/billing')}}" class="btn btn-green btn-red w-100"><i class="fas fa-times text-white button"></i> Cancel Server</a></p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="col-sm-4">
                            <div class="text-container mb-80" data-server-id="{{$server->id}}">
                                <div class="text">
                                    <div class="img-content">
                                        <i class="fa fa-server {{$server->suspended ? 'text-red' : null}}"></i>
                                    </div>
                                    <h4>{{$server->data->name}}</h4>
                                    <p class="mb-20">{{$server->plandata['title']}} Plan ({{$server->plandata['ram']}} GB)</p>
                                    @if($server->suspended)
                                    <p><a href="#" class="btn btn-light-blue w-100 mb-10" disabled="disabled">Suspended</a></p>
                                    <p><a href="#" class="btn btn-green btn-red btn-disabled w-100" disabled="disabled"><i class="fas fa-times text-white button"></i> Cancel Server</a></p>
                                    
                                    @else
                                    <p class="text-blue">Awaiting payment confirmation</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        @empty
                        <div class="col-sm-12">
                            <p class="text-center">You do not have any servers, check out <a href="{{url('plans')}}">our plans</a>!</p>
                        </div>
                        @endforelse
                    </div>
                </div>    
            
            </div>
        </div>
    </div>
    


    

@endsection

@section('script')
<script type="text/javascript">
$(document).ready(function(){

    var planSelector = {!! json_encode(Config::get('plans.planSelector')) !!}
    var selectData = {
        version: null,
        type: null
    }

    $('.select-update').on('change', function() {
        var update = $(this).attr('data-select-update')
        selectData[update] = $(this).val()
        console.log(selectData)

        if(selectData.version != null && selectData.type != null) {
            var recPlan = planSelector[selectData.version][selectData.type]

            $('.recommended-plan').html($('div[data-plan="'+recPlan+'"]').html())
            $('[data-toggle="tooltip"]').tooltip()
        }
    })

});
</script>
@endsection
@section('jquery')

$('button.copy').click(function() {
    navigator.clipboard.writeText($(this).attr('data-link'));
    $(this).html('<i class="fas fa-copy"></i> Copied!');
});
@endsection