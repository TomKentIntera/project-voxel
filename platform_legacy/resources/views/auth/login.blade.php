@extends('templates.main', ['showFooter' => false])

@section('content')

<div class="layout-text right-layout gray-layout padding-bottom60 padding-top60 full-height">
    <div class="container">
        <div class="row">
            @if(isset($errors))
            <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
                <div class="alert alert-danger">
                    <p>Those details don't match anything we have on record. Please try again or <a href="{{ route('password.request') }}">reset your password</a>.</p>
                </div>
            </div>
            @endif
            <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
                <form method="POST" action="{{ route('login') }}" class="login">
                    @csrf
                    <h3 class="text-center mb-20">Login to your Intera Account</h3>
                    <!-- Email Address -->
                    <div>
                        <label>Email</label>
                        <input id="email" class="block mt-1 w-full" type="email" name="email" value="{{old('email')}}" required autofocus></input>
                        
                    </div>

                    <!-- Password -->
                    <div class="mt-4">
                        <label>Password</label>

                        <input id="password" class="block mt-1 w-full"
                                        type="password"
                                        name="password"
                                        required autocomplete="current-password"></input>

                        
                    </div>

                    <!-- Remember Me -->
                    <div class="block mt-4">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        @if (Route::has('password.request'))
                            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif
                    </div>
                    <div class="flex flex-columns mt-4">
                    
                        <x-primary-button class="ml-3 flex w-50">
                            {{ __('Log in') }}
                        </x-primary-button>
                        <a href="{{ route('register') }}" class="btn btn-secondary flex w-50" style="margin-top: -4px">Register</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    
@endsection