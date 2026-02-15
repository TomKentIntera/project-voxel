@extends('templates.main')

@section('content')
    <!-- Layout - Text right  -->
    <div class="layout-text center-layout gray-layout padding-bottom60 padding-top60 section-header-bg full-height" style="background-image:url({{asset('images/headers/dark.png')}});">
        <div class="container">
            <div class="row">
                <div class="col-sm-12 ">
                    <div class="text-container text-center margin-auto content-box" data-aos="fade-left">
                        <h3>Thank you!</h3>
                        <div class="text-content">
                            <div class="text">
                                <p>We'll let you know as soon as a node is available!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Layout - Text right ends here  -->

@endsection

@section('script')
@endsection