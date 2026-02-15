@extends('templates.main')

@section('content')
   
    
    <!-- Layout - Text right  -->
    <div class="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg" style="background-image:url(images/headers/dark.png);">
        <div class="container">
            <div class="row planSelector">
                <div class="col-sm-12">
                    <h2 class="text-center">Welcome To Your Account</h2>
                    
                </div>
                
            </div>
        </div>
    </div>
    <!-- Layout - Text right ends here  -->


    <!-- Pricing Tables -->
    <div class="pricing-tables custom-pricing padding-top50 padding-bottom50">
        <div class="custom-width">
            
            <!--@include('components.referral-cta')-->
            <div class="row">
                <div class="col-sm-4 col-md-3 mb-40px">
                    <div class="account-nav">
                        <a href="{{url('client/billing')}}"><i class="fas fa-money-check"></i> Billing</a>
                        <a href="{{env('SERVICE_DESK_URL')}}"><i class="fas fa-question-circle"></i> Get Support</a>
                        <a href="{{url('plans')}}"><i class="fas fa-server"></i> New Server</a>
                        <!--<a href="{{url('referral')}}"><i class="fas fa-envelope"></i> Referral Programme</a>-->
                        <a href="{{url('logout')}}"><i class="fas fa-door-open"></i> Logout</a>
                    </div>
                </div>  
                <div class="col-sm-8 col-md-9 features-six">
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
                                        <p class="server-date">Created: {{$server->created_at->format('Y-m-d')}}</p>
                                        <p class="mb-20">{{$server->plandata['title']}} Plan ({{$server->plandata['ram']}} GB)</p>
                                        
                                        @if($server->suspended)
                                        <p><a href="#" class="btn btn-light-blue w-100 mb-10" disabled="disabled">Suspended</a></p>
                                        <p><a href="#" class="btn btn-green btn-red btn-disabled w-100" disabled="disabled"><i class="fas fa-times text-white button"></i> Cancel Server</a></p>
                                        
                                        @else
                                        <p><a href="{{url('panel')}}" class="btn btn-green w-100 mb-10"><i class="fas fa-share text-white button"></i> Admin Panel</a></p>
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
                                        <p class="server-date">Created: {{$server->created_at->format('Y-m-d')}}</p>
                                        <p class="mb-20">{{$server->plandata['title']}} Plan ({{$server->plandata['ram']}} GB)</p>
                                        
                                        @if($server->suspended)
                                        <p><a href="#" class="btn btn-light-blue w-100 mb-10" disabled="disabled">Suspended</a></p>
                                        <p><a href="#" class="btn btn-green btn-red btn-disabled w-100" disabled="disabled"><i class="fas fa-times text-white button"></i> Cancel Server</a></p>
                                        
                                        @else
                                        <p><a href="#" class="btn btn-transparent w-100 mb-10" disabled="disabled">&nbsp;</a></p>
                                        <p><a href="#" class="btn btn-light-blue w-100 awaiting-payment" disabled="disabled">Awaiting payment confirmation</a></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        @empty
                        <div class="col-sm-12">
                            <p class="text-center">You do not have any servers, check out <a href="{{url('plans')}}" class="green">our plans</a>!</p>
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