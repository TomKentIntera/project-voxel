<?php
    $plans = [];

    foreach(Config::get('plans.planList') as $plan) {
        if(isset($vaultHunters)) {
            if($vaultHunters && $plan['showVaultHunterPlans']) {
                $plans[] = $plan['title'];
            }
        } else {
            if($plan['showVaultHunterPlans'] === false) {
                $plans[] = $plan['title'];
            }
            
        }
    }

?>
<div class="call-to-action cta-blue cta-bg notification_scroll" style="background-image:url({{asset('images/plan-images/skeleton.png')}}); 
    background-size: 140px;
    background-position: 140px 30px;">
        <div class="custom-width">
            <div class="row">
                <div class="col-sm-6">
                    <h3>Out of stock in your region?</h3>
                    <p>We're always getting new nodes in our regions. If we've not got availability right now, we can let you know as soon as a node becomes available.</p>
                </div>
                <div class="col-sm-6">
                    <div class="buttons">
                        <a class="btn btn-outline btn-large notification_open main">Get notified of availability! <i class="fas fa-long-arrow-alt-right"></i></a>
                    </div>
                </div>

            </div>
            <div class="row notification_container" style="display:none">
                <div class="col-sm-12 mt-20">
                    <p>Select a plan below and and enter your email. We'll let you know as soon as a node becomes available.</p>
                </div>
                <form method="POST" action="{{url('availability')}}">
                    @csrf
                    <div class="col-sm-4 mt-2">
                        <select name="plan" class="notification_plan">
                            @foreach($plans as $plan)
                                @if($loop->first)
                                <option value="{{$plan}}" selected="selected">{{$plan}}</option>
                                @else
                                <option value="{{$plan}}">{{$plan}}</option>
                                @endif  
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3 mt-2">
                        <input type="email" name="email" class="block input" placeholder="Your Email">
                    </div>
                    <div class="col-sm-3 mt-2">
                        <select name="region" class="">
                            @foreach(Config::get('plans.locations') as $loc => $locData))
                                @if($loop->first)
                                <option value="{{$locData['ptero_location']}}" selected="selected">{{$locData['title']}}</option>
                                @else
                                <option value="{{$locData['ptero_location']}}">{{$locData['title']}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2 mt-2">
                        <button type="submit" class="btn btn-green">Get Notified</button>
                    </div>
                </form>
            </div>
        </div>
    </div>