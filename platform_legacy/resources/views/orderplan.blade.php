@extends('templates.main')

@section('content')

    
    
    <!-- Layout - Text right  -->
    <div class="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg full-height" style="background-image:url({{asset('images/headers/dark.png')}});">
        <div class="container">
            <div class="row planSelector">
                <div class="col-sm-6">
                    <h3 class="text-center">Selected Plan</h3>
                    <div class="recommended-plan">
                        <div class="table">
                            <div class="table-img plan-img">
                                <div class="plan-img-ar1">
                                    <img src="{{asset('images/plan-images/'.$plan['icon'])}}" class="img-center img-responsive"
                                    alt="{{$plan['title']}}">
                                </div>
                            </div>
                            <div class="table-flags">
                                @foreach($plan['locations'] as $location)
                                <span class="flag" data-toggle="tooltip" data-placement="bottom" title="{{Config::get('plans.locations.'.$location.'.title')}}"><img src="{{asset('images/flags/'.Config::get('plans.locations.'.$location.'.flag').'.svg')}}" width="48"></span>
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
                            </div>
                        </div>
                    </div>

                    <div class="max-w-330 text-white m-0-auto mt-20">
                        <a href="/plans" class="btn btn-green btn-medium w-100">Change Plan</a>
                    </div>
                </div>
                <div class="col-sm-6 ">
                    <div class="text-container" data-aos="fade-left">
                        <h3>Configure Your Plan</h3>
                        <div class="text-content">
                            <div class="text">
                                <p>Your server will soon be ready - we just need some information from you to get it started!</p>
                            </div>
                        </div>
                        <form method="POST" action="{{url('/plan/configure/'.$plan['name'].'/do')}}">
                            @csrf
                            <input name="plan" type="hidden" value="{{$plan['name']}}">
                        
                            <div class="mb-3">
                                <label for="" class="form-label">What should we name this server?</label>
                                
                                <input class="select-update dark" data-select-update="name" name="name" type="text" value="My Server"></input>
                            </div>

                            <div class="mb-3">
                                <label for="" class="form-label">Where would you like the server to be located?</label>
                                
                                <select class="form-select select-update" name="location" data-select-update="location" aria-label="Default select example">
                                <option value="null">Select a location</option>
                                    @foreach($locations as $location)
                                    @if($location['avilable'])
                                    <option value="{{$location['locationID']}}">{{$location['locationName']}}</option>
                                    @else
                                    
                                    <option value="null" disabled="disabled">{{$location['locationName']}} (Unavalable)</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>

                            
                            <div class="mb-3">
                                <label for="" class="form-label">What version will you be running?</label>
                                
                                <select class="form-select select-update" name="minecraft_version" data-select-update="minecraft_version" aria-label="Default select example">
                                    <option value="null">Select a Minecraft version</option>
                                    @foreach($minecraftVersions as $version)
                                    <option value="{{$version['title']}}">{{$version['title']}}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="" class="form-label">What type of server will you be running?</label>
                                
                                <select class="form-select select-update" name="type" data-select-update="type" aria-label="Default select example">
                                    <option value="null">Select a server type</option>
                                    <option value="vanilla">Vanilla</option>
                                    <option value="spigot">Spigot</option>
                                    <option value="paper">PaperMC</option>
                                    <option value="bukkit">Bukkit</option>
                                    <option value="forge">Forge/Forge Modpack</option>
                                    <option value="bedrock">Bedrock</option>
                                    
                                </select>
                            </div>
                            
                            <div class="mb-3 type_version_selector" style="display:none">
                                <label for="" class="form-label">Select a version for your X server</label>
                                
                                <select class="form-select select-update" name="type_version" data-select-update="type_version" aria-label="Default select example">
                                    <option value="null">Select a server type</option>
                                    
                                </select>
                            </div>

                            <div class="mb-3 mt-20">
                                <button type="submit" class="btn btn-green btn-medium w-100 submit_button" style="display:none">Create Server</submit>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Layout - Text right ends here  -->

    @if($unavailableInSomeLocations)
    <!-- Call to action -->
    <div class="call-to-action cta-red">
        <div class="custom-width">
            <div class="row">
                <div class="col-sm-6">
                    <h3>Looking for other locations?</h3>
                    <p>This plan is unavailable in some locations whilst we deploy more hardware! Please check back later, choose an alternative location or choose a different plan.</p>
                </div>
                <div class="col-sm-6">
                    <div class="buttons">
                        <a href="/plans" class="btn btn-outline btn-large">Select a different plan <i class="fas fa-long-arrow-alt-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Call to action ends here -->
    @endif

@endsection

@section('script')
<script type="text/javascript">
$(document).ready(function(){

    var selectData = {
        minecraft_version: null,
        location: null,
        type_version: null,
        type: null,
        name: null
    }

    $('.select-update').on('change', function() {
        var update = $(this).attr('data-select-update')
        var value =  $(this).val();

        if(value != "null") {
            selectData[update] = value;
        } else {
            selectData[update] = null;
        }

        if(update == 'type' || update == 'minecraft_version') {
            if(value == 'forge' || selectData.type == 'forge') {
                retreiveForgeVersion(selectData.minecraft_version)
            }
        }
        

        console.log(selectData)

        var typesRequiringVersion = [
            'forge', 'bungeecord'
        ];

        if(typesRequiringVersion.includes(selectData.type)) {
            console.log('Type requires version')
            $('.type_version_selector label').text('Select a version for your '+selectData.type+' server')
            $('.type_version_selector').fadeIn();
        } else {
            $('.type_version_selector').fadeOut();
            selectData.type_version = null;
        }

        if(selectData.minecraft_version != null && selectData.type != null && selectData.location != null) {
            if(typesRequiringVersion.includes(selectData.type)) {
                console.log('Type requires version')
                if(selectData.type_version == null) {
                    $('.submit_button').fadeOut();
                    return;
                }
            }
            
            $('.submit_button').fadeIn();
        } else {
            
            $('.submit_button').fadeOut();
        }
    })

    function retreiveForgeVersion(mc_version) {
        var url = '{{url('api/versions/forge')}}'

        $.get( url, function( data ) {
            console.log(data)
            // remove all forge versions from the thing
            selectData.type_version = null;

            $('.type_version_selector select').find('option')
            .remove()
            .end()
            .append('<option value="null">Select a forge version</option>')

            recommendedSelect = mc_version + "-recommended";

            if(!Object.keys(data).includes(recommendedSelect)) {
                recommendedSelect = mc_version + "-latest"
            }

            Object.keys(data).forEach(key => {
                if(key == recommendedSelect) {
                    element = '<option value="'+key.replace('recommended', '').replace('latest', '') + data[key]+'" selected="selected">'+data[key]+' ('+key+')</option>';
                } else {
                    element = '<option value="'+key.replace('recommended', '').replace('latest', '') + data[key]+'">'+data[key]+' ('+key+')</option>';
                }

                
                $('.type_version_selector select').append(element)
            });

            $('.submit_button').fadeIn();
        });
    }

});
</script>
@endsection