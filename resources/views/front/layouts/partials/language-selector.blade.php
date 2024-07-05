@if (isset($langs))
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
                @foreach (ag_lang() as $lang )
                    <li>
                        @if (isset($page) && $page->id == 5 )
                            <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('index'), [], true) }}">
                                <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @endif
                        <!-- -->
                        @if (isset($page) && $page->id != 5)
                            <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route.page', ['page' => $page->translation($lang->code)->slug])) }}">
                                <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @endif
                        <!-- -->
                        @if (isset($group) && isset($cat) && ! $cat)
                            <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['group' => \Illuminate\Support\Str::slug(config('settings.group_path'))]), [], true) }}">
                                <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @endif
                        <!-- -->
                        @if (isset($cat) && $cat && ! $subcat && ! $prod)
                            <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['group' => \Illuminate\Support\Str::slug(config('settings.group_path')), 'cat' => $cat->translation($lang->code)->slug]), [], true) }}">
                                <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @endif
                        <!-- -->
                        @if (isset($subcat) && $subcat && ! $prod)
                            <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['group' => \Illuminate\Support\Str::slug(config('settings.group_path')), 'cat' => $cat->translation($lang->code)->slug, 'subcat' => $subcat->translation($lang->code)->slug]), [], true) }}">
                                <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @endif
                        <!-- -->
                        @if (isset($prod) && $prod)
                            @if (isset($cat) && $cat && ! $subcat)
                                <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['group' => \Illuminate\Support\Str::slug(config('settings.group_path')), 'cat' => $cat->translation($lang->code)->slug, 'subcat' => $prod->translation($lang->code)->slug]), [], true) }}">
                                    <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                    {{ $lang->title->{current_locale()} }}
                                </a>
                            @else
                                <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('catalog.route', ['group' => \Illuminate\Support\Str::slug(config('settings.group_path')), 'cat' => $cat->translation($lang->code)->slug, 'subcat' => $subcat->translation($lang->code)->slug, 'prod' => $prod->translation($lang->code)->slug]), [], true) }}">
                                    <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                    {{ $lang->title->{current_locale()} }}
                                </a>
                            @endif
                        @endif
                        <!-- -->
                        @if (request()->routeIs(['kosarica', 'naplata', 'moj-racun']))
                            <a class=" @if (current_locale() == $lang->code) active @endif" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($lang->code, route('kosarica'), [], true) }}">
                                <img class="lang" style="width:16px;margin-left:5px" src="{{ asset('media/flags/'.Str::lower($lang->code).'.png') }}" alt="">
                                {{ $lang->title->{current_locale()} }}
                            </a>
                        @endif
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</div>
@endif
