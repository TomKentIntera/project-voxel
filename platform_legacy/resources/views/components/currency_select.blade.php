<div class="currency-select">
    <a class=""><span class="flag"><img src="{{asset('images/flags/'.Price::getCurrencyData()['flag'])}}" width="48"></span>{{Price::getCurrencyData()['symbol']}}</a>
    <div class="currency-container" style="display:none">
        @foreach(Price::getSupportedCurrenciesList() as $currency) 
        <span>
            <a href="{{url('currency/'.$currency['symbol'].'/?redirect='.urlencode(Request::url()))}}" class="{{$currency['symbol'] == Price::getCurrency() ? 'selected' : null}}">
                <span class="flag"><img src="{{asset('images/flags/'.$currency['flag'])}}" width="48"></span>
                <span class="currency">{{$currency['currency']}}</span>
            </a>
        </span>
        @endforeach
    </div>
</div>