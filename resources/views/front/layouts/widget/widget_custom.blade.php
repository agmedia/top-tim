<!-- {"title": "Slider Index", "description": "Index main slider."} -->


<section class="tns-carousel mb-0  d-none d-md-block">
    <div class="tns-carousel-inner" data-carousel-options="{&quot;items&quot;: 1,&quot;autoplay&quot;: true, &quot;autoplayTimeout&quot;: 4000, &quot;mode&quot;: &quot;gallery&quot;, &quot;nav&quot;: true, &quot;responsive&quot;: {&quot;0&quot;: {&quot;nav&quot;: true, &quot;controls&quot;: false}, &quot;576&quot;: {&quot;nav&quot;: true, &quot;controls&quot;: true}}}">
        @foreach($data as  $widget)
        <div class="px-0 ">
            <a  href="{{ url($widget['url']) }}"> <img src="{{ $widget['image'] }}" width="3308" height="1267" alt="{{ $widget['title'] }}" class="rounded-3"/></a>
        </div>
        @endforeach
    </div>
</section>
<section class="tns-carousel mb-0  d-block d-md-none">
    <div class="tns-carousel-inner" data-carousel-options="{&quot;items&quot;: 1, &quot;autoplay&quot;: true, &quot;autoplayTimeout&quot;: 4000, &quot;mode&quot;: &quot;gallery&quot;, &quot;nav&quot;: true, &quot;responsive&quot;: {&quot;0&quot;: {&quot;nav&quot;: true, &quot;controls&quot;: false}, &quot;576&quot;: {&quot;nav&quot;: true, &quot;controls&quot;: true}}}">
        @foreach($data as  $widget)
            <div class="px-0 ">
                <a  href="{{ url($widget['url']) }}"> <img src="{{ $widget['image_2'] }}" width="3308" height="1267" alt="{{ $widget['title'] }}" class="rounded-3"/></a>
            </div>
        @endforeach
    </div>
</section>

