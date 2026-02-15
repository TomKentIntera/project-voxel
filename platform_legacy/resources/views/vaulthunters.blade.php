@extends('templates.main')

@section('content')

    @include('components.banner')
    <!-- Default Page Header -->
    <div class="default-header shared-page">
        <div class="custom-width">
            <div class="row">
                <div class="col-sm-7">
                    <div class="header-text">
                        <h2>Get into the best RPG modpack in Minecraft</h2>
                        <p>Slay monsters, craft your gear, complete the vault! Intera can have your Vault Hunters server up and running within as little as 5 minutes!</p>
                        <h4>Starting at</h4>
                        <h3>{{Price::getCurrencySymbol()}}{{Price::getPlanPrice('pog')}}/monthly</h3>
                    </div>
                    <div class="buttons">
                        <a href="#plans" class="btn btn-green btn-large">Get Started Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Default Page Header ends here -->

    <!-- Pricing Tables -->
    <div class="pricing-tables custom-pricing padding-top50 padding-bottom50" id="plans">
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
                @if($plan['showVaultHunterPlans'])
                @include('components.plan.plan', ['plan' => $plan, 'locationsCache' => $locationsCache, 'modded' => true, 'modId' => 1])
                @endif
                @endforeach
                
            </div>
        </div>
    </div>

    @include('components.features')
    <!-- Pricing Tables content ends here -->

    @include('components.no_availability', ['vaultHunters' => true])

    @include('components.features_main')


    <!-- Faq -->
    <div class="faq padding-bottom50 padding-top50">
        <div class="custom-width">
            <h3>Frequently Asked Questions</h3>
            <div class="accordion">
                @foreach(Config::get('faqs') as $faq)
                @if($faq['showOnHome'])
                <div class="accordion-item">
                    <a>{{$faq['title']}}</a>
                    <div class="content">
                        <p>{!! $faq['content'] !!}</p>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
    <!-- Faq ends here -->
    
    @include('components.panel')


    

    <!-- Call to action -->
    <div class="call-to-action">
        <div class="custom-width">
            <div class="row">
                <div class="col-sm-6">
                    <h3>Ready to get started?</h3>
                    <p>Just select a plan and be online in 5 minutes!</p>
                </div>
                <div class="col-sm-6">
                    <div class="buttons">
                        <a href="/plans" class="btn btn-outline btn-large">Select a plan <i class="fas fa-long-arrow-alt-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Call to action ends here -->
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