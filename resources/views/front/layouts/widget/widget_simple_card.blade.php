<!-- {"title": "Banneri", "description": "Widget za bannere"} -->
<section class=" py-3 " >
    <div class="row  mt-2 mt-lg-5 ">
        @foreach ($data as $widget)
            <div class="col-lg-12 col-xl-{{ $widget['width'] }} mb-grid-gutter">
                <div class="d-sm-flex justify-content-between align-items-center  overflow-hidden rounded-3" style="background-color:#fff">
                    <div class="py-4 my-2 my-md-0  px-4 ms-md-3 text-center text-sm-start" >
                        <h2 class="font-title">{{ $widget['title'] }}</h2>
                        <p class="text-muted pb-2">{!! $widget['subtitle'] !!} </p><a class="btn btn-primary btn-shadow btn-sm" href="{{ url($widget['url']) }}">{{ __('front/ricekakis.pogledajte_ponudu') }} <i class="ci-arrow-right ms-2 me-n1"></i></a>
                    </div>
                    <img class="d-block mx-auto mx-lg-1" src="{{ $widget['image'] }}" width="220" height="220" alt="{{ $widget['title'] }}">
                </div>
            </div>
        @endforeach
    </div>
</section>



