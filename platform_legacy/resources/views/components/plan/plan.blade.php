<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 mb-20" data-plan="{{$plan['name']}}">
                    <div class="table">
                        <div class="table-img plan-img">
                            <div class="plan-img-ar1">
                                <img src="{{asset('images/plan-images/'.$plan['icon'])}}" class="img-center img-responsive"
                                alt="{{$plan['title']}}">
                            </div>
                        </div>
                        <div class="table-flags">
                            <?php
                                $hasAvailableLocation = false;
                            ?>
                            @foreach($plan['locations'] as $location)
                            
                            <?php 
                                $locationAvailableRAM = 0;
                                if($locationsCache != null) {
                                   
                                    foreach($locationsCache['locations'] as $locationData) {
                                        if($locationData['short'] === Config::get('plans.locations.'.$location.'.ptero_location')) {
                                            $locationAvailableRAM = $locationData['maxFreeMemory'];
                                        }
                                    }
                                }
                                
                                
                            ?>
                            @if($locationAvailableRAM >= 1024 * $plan['ram'])
                            <span class="flag" data-toggle="tooltip" data-available="{{$locationAvailableRAM ?? null}}" data-placement="bottom" title="{{Config::get('plans.locations.'.$location.'.title')}}"><img src="{{asset('images/flags/'.Config::get('plans.locations.'.$location.'.flag').'.svg')}}" width="48"></span>
                            <?php 
                                $hasAvailableLocation = true;
                            ?>
                            @else
                            <span class="flag unavailable" data-toggle="tooltip" data-available="{{$locationAvailableRAM ?? null}}" data-plan="{{$plan['title']}}" data-placement="bottom" title="{{Config::get('plans.locations.'.$location.'.title')}} (Unavailable)"><img src="{{asset('images/flags/'.Config::get('plans.locations.'.$location.'.flag').'.svg')}}" width="48"></span>
                            @endif
                            @endforeach
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
                            <p class="plan-price">{{Price::getCurrencySymbol()}}{{Price::getPlanPrice($plan['name'])}}/month</p>
                            
                            <div class="buttons">
                                @if($hasAvailableLocation)
                                    @if($modded ?? false)
                                    <a href="/plan/configure/{{$plan['name']}}/mod/{{$modId}}" class="btn btn-green btn-medium btn-90">Order Now</a>
                                    @else
                                    <a href="/plan/configure/{{$plan['name']}}" class="btn btn-green btn-medium btn-90">Order Now</a>
                                    @endif
                                @else
                                <a class="btn btn-blue btn-medium btn-90 notification_open" data-plan="{{$plan['title']}}">Get Notified</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>