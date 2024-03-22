
<div class="language">
    <div class="dropdown hover-dropdown">
        <button class="dropdown-toggle btn btn-sm btn-default px-1 ps-3 text-white" type="button" data-bs-toggle="dropdown"><img class="lang" style="width:16px" src="{{ asset('media/flags/' . session('locale') . '.png') }}" alt=""> {{ \Illuminate\Support\Str::upper(current_locale()) }} </button>
        <ul class="dropdown-menu">
            @if (isset($langs))
                @foreach ($langs as $lang)
                    <li>
                        <a class="dropdown-item @if (current_locale() == $lang['code']) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang['code'], $lang['slug'], [], true) }}">{{ $lang['title'] }}</a>
                    </li>
                @endforeach
            @else
                @foreach (ag_lang() as $lang)
                    <li>
                        <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('index'), [], true) }}">
                            <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                            {{ $lang->title->{current_locale()} }}
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</div>
