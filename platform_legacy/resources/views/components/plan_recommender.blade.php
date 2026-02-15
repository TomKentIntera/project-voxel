<!-- Layout - Text right  -->
<div class="layout-text right-layout gray-layout padding-bottom60 padding-top60">
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