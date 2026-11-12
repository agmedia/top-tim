<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PriceListPublication;
use App\Services\PriceListPublisher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PriceListController extends Controller
{
    public function index()
    {
        $publications = Schema::hasTable('price_list_publications')
            ? PriceListPublication::publicArchive()->paginate(30)
            : new LengthAwarePaginator([], 0, 30);
        return view('front.price-lists', compact('publications'));
    }

    public function latest(PriceListPublisher $publisher)
    {
        abort_unless(Schema::hasTable('price_list_publications'), 404);
        return $this->respond(PriceListPublication::publicArchive()->firstOrFail(), $publisher);
    }

    public function download(int $publication, PriceListPublisher $publisher)
    {
        abort_unless(Schema::hasTable('price_list_publications'), 404);
        return $this->respond(PriceListPublication::publicArchive()->findOrFail($publication), $publisher);
    }

    private function respond(PriceListPublication $publication, PriceListPublisher $publisher)
    {
        abort_unless($publication->published_at >= now()->subDays(config('price_lists.archive_days'))
            && $publisher->valid($publication), 404);
        return response()->download(Storage::disk(config('price_lists.disk'))->path($publication->path), $publication->filename, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
