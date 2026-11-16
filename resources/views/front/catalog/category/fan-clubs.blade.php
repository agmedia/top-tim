<section class="py-3" aria-label="Klubovi">
    <div class="row g-3">
        @forelse ($clubs as $club)
            <div class="col-6 col-md-4 col-xl-3">
                <a class="card h-100 text-center text-decoration-none" href="{{ route('catalog.route', ['group' => $cat->group, 'cat' => $cat->slug, 'subcat' => $club->slug]) }}">
                    @if ($club->image && $club->image !== 'media/avatars/avatar0.jpg')
                        <img class="card-img-top p-3" src="{{ asset($club->image) }}" alt="Grb kluba {{ $club->title }}" width="240" height="200" style="height:200px;object-fit:contain" loading="lazy">
                    @else
                        <div class="d-flex align-items-center justify-content-center p-3" style="height:200px" aria-hidden="true"><i class="ci-star fs-1 text-muted"></i></div>
                    @endif
                    <div class="card-body"><h2 class="h5 mb-0">{{ $club->title }}</h2></div>
                </a>
            </div>
        @empty
            <p class="text-muted">Ponuda klubova uskoro stiže.</p>
        @endforelse
    </div>
</section>
