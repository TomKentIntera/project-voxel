@extends('templates.main')

@section('content')


    
    <!-- Layout - Text right  -->
    <div class="layout-text right-layout gray-layout padding-bottom60 padding-top60 section-header-bg" style="background-image:url(images/headers/dark.png);">
        <div class="container">
            <div class="row planSelector">
                <div class="col-sm-12">
                    <h2 class="text-center">Frequently Asked Questions</h2>
                    
                </div>
                
            </div>
        </div>
    </div>
    <!-- Layout - Text right ends here  -->
    <!-- Faq -->
    <div class="faq padding-bottom50 padding-top50">
        <div class="custom-width">
            <div class="accordion">
                @foreach(Config::get('faqs') as $faq)
                <div class="accordion-item">
                    <a>{{$faq['title']}}</a>
                    <div class="content">
                        <p>{!! $faq['content'] !!}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- Faq ends here -->

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
