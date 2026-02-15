@extends('emails.template')

@section('content')
    <!-- Body content -->
    <tr>
        <td class="content-cell">
            <div class="f-fallback">
                <h1>Good news!</h1>
                <p>You requested that we let you know when the <strong class="text-green">{{$plan['name']}}</strong> plan was available in {{$region['title']}}. The wait is over, we've just added new nodes to this location! Check it out by visiting our <a href="{{url($planLink ?? 'plans')}}">plans page</a>.</p>
                <!-- Action -->
                <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td align="center">
                            <!-- Border based button
               https://litmus.com/blog/a-guide-to-bulletproof-buttons-in-email-design -->
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url($planLink ?? 'plans') }}" class="f-fallback button" target="_blank">View Plans</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <table class="body-sub" role="presentation">
                    <tr>
                        <td>
                            <p class="f-fallback sub">If you’re having trouble with the button above, copy and paste the URL
                                below into your web browser.</p>
                            <p class="f-fallback sub"><a href="{{ url($planLink ?? 'plans') }}">{{ url($planLink ?? 'plans') }}</a></p>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
@endsection
