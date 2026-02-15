<!-- Navbar content -->
<nav class="navbar navbar-default dark navbar-sticky no-background bootsnav">
    <!-- Start Top Search -->
    <div class="top-search">
        <div class="container">
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" placeholder="Search">
                <span class="input-group-addon close-search"><i class="fa fa-times"></i></span>
            </div>
        </div>
    </div>
    <!-- End Top Search -->

    <div class="custom-width">
        

        <!-- Start Header Navigation -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                <i class="fa fa-bars"></i>
            </button>
            <a class="navbar-brand" href="/"><img src="{{asset('images/logo.png')}}" class="logo" alt=""></a>
        </div>
        <!-- End Header Navigation -->

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="navbar-menu">
            <ul class="nav navbar-nav navbar-right" data-in="fadeIn" data-out="fadeOut">
                <li>
                    <a href="/" class="active">Home</a>
                </li>
                
                <li>
                    <a href="/plans" class="">Plans</a>
                </li>
                <li>
                    <a href="/vaulthunters" class=""><img src="{{asset('images/vh_logo.png')}}"></a>
                </li>
                
                <li>
                    <a href="/faqs" class="">FAQs</a>
                </li>
                
                <li><a href="/client" class="btn btn-primary"><i class="fa fa-user"></i> Client Area</a></li>
                <li>@include('components.currency_select')</li>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div>
    
</nav>
<!-- Navbar Content ends here -->