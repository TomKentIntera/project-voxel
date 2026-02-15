<?php
    if(!isset($showFooter)) {
        $showFooter = true;
    }
?>
<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
    <!-- Meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Intera Games - Premium Minecraft Hosting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Intera Games">
    <meta name="keywords" content="minecraft,hosting,bukkit,spigot,paper,bungeecord,vanilla,gaming">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('favicon.png')}}">
    <!-- Page title -->
    <title>Intera Games - @yield('title', 'Premium Minecraft Hosting')</title>
    <!-- CSS FILES -->
    <link rel="stylesheet" href="{{asset('css/fontawesome-icons/css/all.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/bootsnav.css')}}">
    <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('style/other/aos.css')}}">
    <link rel="stylesheet" href="{{asset('style/other/animate.css')}}">
    <link rel="stylesheet" href="{{asset('style/fakeloader.css')}}">
    <!-- Main css style -->
    <link rel="stylesheet" href="{{asset('style/style.css')}}">
    <!-- main responsive css file -->
    <link rel="stylesheet" href="{{asset('style/responsive.css')}}">
    <!-- CSS FILES end -->
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JQ07S6C94V"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-JQ07S6C94V');
    </script>
    <!-- Meta Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '678931010813378');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=678931010813378&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
</head>

<body>
    <script data-jsd-embedded data-key="470112ec-917c-4d34-87e2-9cfee53891cd" data-base-url="https://jsd-widget.atlassian.com" src="https://jsd-widget.atlassian.com/assets/embed.js"></script>

    <div id="fakeLoader"></div>

    @include('components.navbar')

    @yield('content')

    @if($showFooter)
    <!-- Dark Footer -->
    <footer class="light-footer dark-footer">
        <div class="custom-width">
            <div class="row">
                <div class="col-sm-2">
                    <h4>Contact</h4>
                    <div class="location-info">
                        <h5>423 Birmingham Road, Redditch, UK</h5>
                        <h5><i class="fa fa-envelope"></i> info@intera.digital</h5>
                    </div>
                </div>
                <div class="col-sm-8">
                    <h4>Important Links</h4>
                    <ul>
                        <li><a href="plans">Hosting Plans</a></li>
                        <li><a href="faqs">FAQs</a></li>
                        <li><a href="helpdesk">Support</a></li>
                    </ul>
                </div>
               
                
                <div class="col-sm-2">
                    <h4>Social Media</h4>
                    <div class="social-media">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"> <i class="fab fa-google"></i></a>
                        <a href="#"> <i class="fab fa-linkedin-in"></i></a>
                        <a href="#"> <i class="fab fa-instagram"></i></a>
                    </div>
                </div>
               
            </div>
        </div>
    </footer>
    <!-- Daek Footer ends here -->

    <!-- under footer -->
    <div class="under-footer">
        <div class="custom-width">
            <div class="row">
                <div class="col-sm-8">
                    <div class="under_footer_links">
                        <a href="/helpdesk">Contact Us</a>
                        <a href="/terms">Terms of services</a>
                        <a href="/privacy-policy">Privacy Policy</a>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- under footer section ends here-->
    @endif

    <!-- JAVASCRIPT FILES -->
    <script src="{{asset('js/jquery.js')}}"></script>
    <!--Fake Loader -->
    <script src="{{asset('js/fakeLoader.min.js')}}"></script>
    <!-- contact form -->
    <script src="{{asset('js/validator.js')}}"></script>
    <script src="{{asset('js/contact.js')}}"></script>
    <!-- bootstrap-->
    <script src="{{asset('js/bootsnav.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>
    <!-- AOS animation -->
    <script src="{{asset('js/aos.js')}}"></script>
    <!-- custom javascript file -->
    <script src="{{asset('js/custom.js')}}"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        @yield('jquery')
    });
    </script>
    @yield('script')
</body>

</html>