<?php

namespace App\Models\Back\Catalog\Options;

use App\Models\Back\Settings\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Options extends Model
{

    /**
     * @var string
     */
    protected $table = 'options';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $locale = 'en';


    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->locale = current_locale();
    }


    /**
     * @param null  $lang
     * @param false $all
     *
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function translation($lang = null, bool $all = false)
    {
        if ($lang) {
            return $this->hasOne(OptionsTranslation::class, 'option_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(OptionsTranslation::class, 'option_id');
        }

        return $this->hasOne(OptionsTranslation::class, 'option_id')->where('lang', $this->locale);
    }


    /**
     * @param $value
     *
     * @return mixed
     */
    public function getGroupAttribute($value)
    {
        return $this->translation->group_title;
    }


    /**
     * Validate new category Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->validate([
            'title.*' => 'required',
            'type'    => 'required',
            'item'    => 'required'
        ]);

        $this->request = $request;

        return $this;
    }


    /**
     * Store new category.
     *
     * @return false
     */
    public function create()
    {
        $group = $this->request->input('title')[config('app.locale')] ?? 'hr';

        foreach ($this->request->input('item') as $item) {
            $id = $this->insertGetId([
                'group'      => Str::slug($group),
                'type'       => $this->request->input('type'),
                'value'      => $item['color'] ?? '#000000',
                'value_opt'  => $item['color_opt'] ?? null,
                'option_sku'  => $item['option_sku'] ?? null,
                'data'       => '',
                'sort_order' => $item['sort_order'] ?? 0,
                'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                'updated_at' => Carbon::now()
            ]);

            if ($id) {
                OptionsTranslation::create($id, $this->request, $item);
            } else {
                return false;
            }
        }

        return $this->find($id);
    }


    /**
     * @param Category $category
     *
     * @return false
     */
    public function edit()
    {
        $values = Options::query()->where('group', Str::slug($this->group))->get();
        $group  = $this->request->input('title')[config('app.locale')] ?? 'hr';
        $items  = collect($this->request->input('item'));



        foreach ($values as $value) {
            $item = $items->where('id', $value->id);

            /*if ($item->first()['id'] == 2) {
                dd($item->first(), $value);
            }*/

            if ( ! empty($item->first())) {
                $saved = $value->update([
                    'group'      => Str::slug($group),
                    'type'       => $this->request->input('type'),
                    'value'      => $item->first()['color'] ?? '#000000',
                    'value_opt'  => $item->first()['color_opt'] ?? null,
                    'option_sku'  => $item->first()['option_sku'] ?? null,
                    'sort_order' => $item->first()['sort_order'] ?? 0,
                    'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                    'updated_at' => Carbon::now()
                ]);



                if ($saved) {
                    OptionsTranslation::edit($value->id, $this->request, $item->first());
                } else {
                    return false;
                }
            }
        }

        foreach ($items->where('id', '==', '0') as $item) {
            $id = $this->insertGetId([
                'group'      => Str::slug($group),
                'type'       => $this->request->input('type'),
                'value'      => $item['color'] ?? '#000000',
                'value_opt'  => $item['color_opt'] ?? null,
                'option_sku'  => $item['option_sku'] ?? null,
                'data'       => '',
                'sort_order' => $item['sort_order'] ?? 0,
                'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                'updated_at' => Carbon::now()
            ]);

            if ($id) {
                OptionsTranslation::create($id, $this->request, $item);
            } else {
                return false;
            }
        }

        if ($items->count() < $values->count()) {
            $diff = $values->diffUsing($items, function ($one, $other) {
                return $other['id'] - $one['id'];
            });

            if ($diff->count()) {
                foreach ($diff as $item) {
                    Options::query()->where('id', $item['id'])->delete();
                    OptionsTranslation::query()->where('option_id', $item['id'])->delete();
                }
            }
        }

        return true;
    }


    /**
     * @return array
     */
    public function getList()
    {
        $response = [];
        $values   = Options::query()->orderBy('sort_order','asc')->get();

        foreach ($values as $value) {
            $response[$value->group]['group']   = $value->translation->group_title;
            $response[$value->group]['items'][] = [
                'id'         => $value->id,
                'title'      => $value->translation->title,
                'value'      => $value->color,
                'option_sku'  => $value->option_sku,
                'value_opt'  => $value->color_opt,
                'sort_order' => $value->sort_order
            ];
        }

        return $response;
    }


    /**
     * @return array
     */
    public static function getColorList()
    {
        $response = [];
        $values   = Options::query()->where('type', 'color')->orderBy('sort_order','asc')->get();

        foreach ($values as $value) {
            $response[] = [
                'id'         => $value->id,
                'title'      => $value->translation->title,
                'value'      => $value->color,
                'value_opt'  => $value->color_opt,
                'option_sku'  => $value->option_sku,
                'sort_order' => $value->sort_order
            ];
        }

        return $response;
    }
}
