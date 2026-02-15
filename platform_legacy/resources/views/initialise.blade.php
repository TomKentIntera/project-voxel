@extends('templates.main')

@section('content')

    
    
    <!-- Layout - Text right  -->
    <div class="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg full-height" style="background-image:url({{asset('images/headers/dark.png')}});">
        <div class="container">
            <div class="row planSelector">
                <div class="col-sm-6">
                    <h3 class="text-center">Your Plan</h3>
                    <div class="recommended-plan">
                        <div class="table">
                            <div class="table-img plan-img">
                                <div class="plan-img-ar1">
                                    <img src="{{asset('images/plan-images/'.$plan['icon'])}}" class="img-center img-responsive"
                                    alt="{{$plan['title']}}">
                                </div>
                            </div>
                            <div class="table-flags">         
                                <span class="flag" data-toggle="tooltip" data-placement="bottom" title="{{Config::get('plans.locations.'.$serverData->config->location.'.title')}}"><img src="{{asset('images/flags/'.Config::get('plans.locations.'.$serverData->config->location.'.flag').'.svg')}}" width="48"></span>
                            </div>
                            @if($plan['ribbon'])
                            <div class="table-ribbon">
                                <span class="ribbon">{{$plan['ribbon']}}</span>
                            </div>
                            @endif
                            <div class="table-content">
                                <h4>{{$plan['title']}}</h4>
                                <p class="plan-ram">{{$plan['ram']}} GB</p>

                                @if(isset($plan['bullets']))
                                @if(count($plan['bullets']) > 0)
                                <div class="table-list">
                                    <ul>
                                        @foreach($plan['bullets'] as $bullet)
                                        <li><i data-aos="zoom-in" class="fa fa-plus aos-init aos-animate"></i> {{$bullet}}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-sm-6 ">
                    <div class="text-container" data-aos="fade-left">
                        <h3>Welcome to your Server</h3>
                        <div class="text-content text-white">
                            <div class="text">
                                <p>We're just waiting for your payment to complete, then we'll automatically trigger the creation of your server!</p>
                                <p class="mt-10">The server we're creating is:</p>
                                <ul class="ul-none mt-10">
                                    @foreach($serverConfigLines as $line)
                                    <li class="server-row"><i class="fas {{$line['icon']}} mr-5"></i><strong>{{$line['title']}}:</strong> {{$line['value']}}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 initialise-container mt-20">
                    <p class="text-center text-white">
                        <i class="fas fa-spinner fa-spin text-white"></i> Awaiting payment completion & server initialisation...
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- Layout - Text right ends here  -->

    <div class="init_complete" style="display:none">
        <div class="text-center">
            <h4>You're ready to game!</h4>
            <p class="mb-20">Your payment has completed and your server has finished installing, all you need to do now is log into the panel and start your server.</p>
            <p><a href="{{url('panel')}}" class="btn btn-green btn-large">Login to the control panel <i class="fas fa-lock-open text-white"></i></a></p>
        </div>
    </div>


@endsection

@section('script')
<script type="text/javascript">
$(document).ready(function(){

    var serverID = "{{$serverData->uuid}}";
    var updateURL = "{{url('api/server/isInitialised/'.$serverData->uuid)}}";
    var panelURL = "{{url('panel')}}";
    var isInitialised = false;
    var tempWait = 0;

    interval = setInterval(() => {
        updateInitialised()
    }, 2000);

    function updateInitialised() {
        $.get( updateURL, function( data ) {
            console.log(data)
            if(isInitialised == false) {
                // check if we're initialised now
                if(data.initialised) {
                    // update the content of the initalise-container
                    $('.initialise-container p').html($('.init_complete').html())
                    isInitialised = true;
                }
            }


        });
    }

    
    

});
</script>
<script>
    gtag("event", "purchase", {
        transaction_id: "{{$serverData->uuid}}",
        value: {{$serverData->planPrice}},
        currency: "{{Price::getCurrency()}}",
        items: [
         {
          item_id: "{{$serverData->plan}}",
          item_name: "{{$plan['title']}}",
          price: {{$serverData->planPrice}},
          quantity: 1
        }]
    });

    fbq('track', 'Purchase', {value: {{$serverData->planPrice}}, currency: '{{Price::getCurrency()}}'}, {eventID: '{{$serverData->uuid}}'});

    </script>
@endsection