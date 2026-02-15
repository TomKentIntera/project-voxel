@extends('templates.main')

@section('content')

    <div class="call-to-action cta-thin cta-red">
        <div class="custom-width">
            <div class="row">
                <div class="col-sm-12">
                    <p>LIMITED OFFER: Save 25% on your first month with coupon code <span class="coupon">FIRST25</span> at checkout.</p>
                </div>
                
            </div>
        </div>
    </div>

    
    
    <!-- Layout - Text right  -->
    <div class="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg" style="background-image:url(images/headers/dark.png);">
        <div class="container">
            <div class="row planSelector">
                <div class="col-sm-6">
                    <h3 class="text-center">Recommended Plan</h3>
                    <div class="recommended-plan">
                        <div class="table">
                            <div class="table-img plan-img">
                                <img data-aos="fade-up" data-aos-delay="100" src="images/plan-images/parrot.png" class="img-center img-responsive"
                                    alt="Please select a plan">
                            </div>
                            
                            <div class="table-content">
                                <h4>Select An Option</h4>
                                <p class="plan-ram">
                                    On the right
                                </p>
                
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 ">
                    <div class="text-container" data-aos="fade-left">
                        <h3>What plan do I need?</h3>
                        <div class="text-content">
                            <div class="text">
                                <p>Not sure what plan is best for you? Use our plan helper below to understand what plan might be best for your needs.</p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="" class="form-label">What version will you be running?</label>
                            
                            <select class="form-select select-update" data-select-update="version" aria-label="Default select example">
                            <option>Select a Minecraft version</option>
                                @foreach(Config::get('plans.planSelector') as $version => $entities)
                                <option value="{{$version}}">{{$version}}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="" class="form-label">What type of server will you be running?</label>
                            
                            <select class="form-select select-update" data-select-update="type" aria-label="Default select example">
                                <option>Select a server type</option>
                                @foreach(Config::get('plans.planSelector_types') as $type)
                                <option value="{{$type}}">{{$type}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Layout - Text right ends here  -->

    <!-- Pricing Tables -->
    <div class="pricing-tables custom-pricing padding-top50 padding-bottom50">
        <div class="custom-width">
            <div class="row">
                <div class="main-title text-center">
                    <h2>Our Minecraft Plans</h2>
                    <p>We offer a range of plans to suit your needs. All of our plans come with the same features, so no need to pay extra to get access to basic features like MySQL databases, FTP access or the plugin auto-installer. </p>
                </div>
                <?php
                $locationsCache = json_decode(Storage::disk('local')->get('locations.json'), true);
                ?>
                @foreach(Config::get('plans.planList') as $plan)
                @if($plan['showVaultHunterPlans'] === false)
                @include('components.plan.plan', ['plan' => $plan, 'locationsCache' => $locationsCache])
                @endif
                @endforeach
                
            </div>
        </div>
    </div>

    @include('components.larger_plans')

    <!-- Pricing Tables content ends here -->
    @include('components.features')


    @include('components.no_availability')


    <!-- Features style three -->
    <div class="features-six padding-bottom50 padding-top50">
        <div class="custom-width">
            <div class="main-title text-center">
                <h2>Why choose Intera?</h2>
                <p>There are hundreds of Minecraft server hosts out there - so why choose Intera Games? </p>
            </div>
            <div class="row">
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <h4>Reliability</h4>
                            <p>We maintain a 99.9% uptime on all of our servers and take a proactive approach to hardware and maintenance.</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-rocket"></i>
                            </div>
                            <h4>High Performance</h4>
                            <p>All of our servers run on high performance hardware including DDR4 RAM and nVME SSDs. We don't compromise on performance!</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-comments"></i>
                            </div>
                            <h4>Excellent Support</h4>
                            <p>Running your own server comes with its challenges. We're on hand to assist with technical questions 365 days a year.</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-microchip"></i>
                            </div>
                            <h4>Mod/plugin installer</h4>
                            <p>Our panel allows you to quickly and easily install plugins and mods for your server from popular plugin/mod repositories.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-hdd"></i>
                            </div>
                            <h4>Unlimited SSD space</h4>
                            <p>We offer unlimited* SSD space with all of our Minecraft servers so you'll never run out of space for worlds and mods.</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-server"></i>
                            </div>
                            <h4>FTP Access</h4>
                            <p>We provide FTP access to all of our servers so you can upload your own Jars and mods, or download your world for your own backups.</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-database"></i>
                            </div>
                            <h4>Automated backups</h4>
                            <p>All of our servers are backed up automatically on a daily schedule, so you'll never have to worry about losing your hard work!</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="text-container">
                        <div class="text">
                            <div class="img-content">
                                <i class="fa fa-exchange-alt"></i>
                            </div>
                            <h4>Unlimited Slots</h4>
                            <p>Unlike other hosts we don't limit the number of slots you can have. You're free to let as many players on as the server can handle!</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="buttons mb-20">
                <a href="#" class="btn btn-green btn-large">Get more information <i class="fas fa-long-arrow-alt-right"></i></a>
            </div>

            <div class="mt-20">
                <p class="text-small text-center">* Subject to fair use</p>
            </div>
        </div>
    </div>
    <!--  Features style three ends here -->


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