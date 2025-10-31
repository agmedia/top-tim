<?php

namespace App\Http\Controllers\Back\Widget;

use App\Models\Back\Settings\Settings;
use App\Models\Back\Widget\Widget;
use App\Models\Back\Widget\WidgetGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WidgetController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = (new WidgetGroup())->newQuery();

        if ($request->has('group')) {
            $query->where('group_id', $request->input('group'));
        }

        $groups = $query->with('widgets')->paginate(config('settings.pagination.items'));

        return view('back.widget.index', compact('groups'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->has('group')) {
            $sizes  = (new Widget())->sizes();
            $selected = WidgetGroup::where('id', $request->input('group'))->first();

            if ($selected) {
                return view('back.widget.templates.' . $selected->template, compact('selected', 'sizes'));
            }
        }

        abort(401);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Ako se uploada klasičan file za image_2, validiraj ga kao sliku.
        if ($request->hasFile('image_2')) {
            $request->validate([
                'image_2' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
        }

        $widget = new Widget();
        $stored = $widget->validateRequest($request)->setUrl()->store();

        if ($stored) {
            // Postojeća logika za prvu sliku (Slim JSON / image_long)
            if (Widget::hasImage($request)) {
                $stored->resolveImage($request);
            }

            // NOVO: druga slika (podržava i Slim JSON i klasičan file)
            if (Widget::hasImage2($request)) {
                $stored->resolveImage2($request);
            }

            $this->flush($stored);

            return redirect()->route('widgets')->with(['success' => 'Widget je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! Desila se greška sa snimanjem widgeta.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $query = (new Widget())->newQuery();

        $widget = $query->where('id', $id)->with('group')->first();
        $sizes  = $widget->sizes();

        if ( ! $widget) {
            abort(401);
        }

        if ($widget->group) {
            $selected = $widget->group;

            $widget->data = unserialize($widget->data);
            $widget->target = isset($widget->data['group']) ? $widget->data['group'] : null;
            $widget->links = collect()->flatten()->toJson();

            if (isset($widget->data['list'])) {
                $widget->links = collect($widget->data['list'])->flatten()->toJson();
            }

            return view('back.widget.templates.' . $widget->group->template, compact('selected', 'sizes', 'widget'));
        }

        $groups = WidgetGroup::where('status', 1)->get();

        return view('back.widget.edit', compact('widget', 'groups', 'sizes'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Ako se uploada klasičan file za image_2, validiraj ga kao sliku.
        if ($request->hasFile('image_2')) {
            $request->validate([
                'image_2' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
        }

        $widget = new Widget();
        $updated = $widget->validateRequest($request)->setUrl()->edit($id);

        if ($updated) {
            // Postojeća logika za prvu sliku
            if (Widget::hasImage($request)) {
                $updated->resolveImage($request);
            }

            // NOVO: druga slika
            if (Widget::hasImage2($request)) {
                $updated->resolveImage2($request);
            }

            $this->flush($updated);

            return redirect()->back()->with(['success' => 'Widget je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! Desila se greška sa snimanjem widgeta.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        if (isset($request['data']['id'])) {
            return response()->json(
                Widget::where('id', $request['data']['id'])->delete()
            );
        }
    }


    /**
     * @param Widget $widget
     */
    private function flush(Widget $widget): void
    {
        $widget_group = WidgetGroup::where('id', $widget->group_id)->first();

        Cache::forget('wg.' . $widget_group->id);
        Cache::forget('wg.' . $widget_group->slug);
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/
    // API ROUTES

    public function getLinks(Request $request)
    {
        if ($request->has('type')) {
            /*if ($request->input('type') == 'category') {
                return response()->json(Category::getList());
            }
            if ($request->input('type') == 'page') {
                return response()->json(Blog::published()->pluck('title', 'id'));
            }*/
        }

        return response()->json([
            'id' => 0,
            'text' => 'Molimo odaberite tip linka..'
        ]);
    }
}
