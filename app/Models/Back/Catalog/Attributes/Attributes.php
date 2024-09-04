<?php

namespace  App\Models\Back\Catalog\Attributes;

use App\Models\Back\Settings\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Attributes extends Model
{

    /**
     * @var string
     */
    protected $table = 'attributes';

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
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

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
            return $this->hasOne(AttributesTranslation::class, 'attribute_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(AttributesTranslation::class, 'attribute_id');
        }

        return $this->hasOne(AttributesTranslation::class, 'attribute_id')->where('lang', $this->locale);
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
            'type' => 'required',
            'item' => 'required'
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
                'group'       => Str::slug($group),
                'type'        => $this->request->input('type'),
                'sort_order'  => $item['sort_order'] ?? 0,
                'status'      => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                'updated_at'  => Carbon::now()
            ]);

            if ($id) {
                AttributesTranslation::create($id, $this->request, $item);
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
        $values = Attributes::query()->where('group', Str::slug($this->group))->get();
        $group = $this->request->input('title')[config('app.locale')] ?? 'hr';
        $items = collect($this->request->input('item'));

        foreach ($values as $value) {
            $item = $items->where('id', $value->id);

            if ( ! empty($item->first())) {
                $saved = $value->update([
                    'group'       => Str::slug($group),
                    'type'        => $this->request->input('type'),
                    'sort_order'  => $item->first()['sort_order'] ?? 0,
                    'status'      => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                    'updated_at'  => Carbon::now()
                ]);



                if ($saved) {
                    AttributesTranslation::edit($value->id, $this->request, $item->first());
                } else {
                    return false;
                }
            }
        }

        foreach ($items->where('id', '==', '0') as $item) {
            $id = $this->insertGetId([
                'group'       => Str::slug($group),
                'type'        => $this->request->input('type'),
                'sort_order'  => $item['sort_order'] ?? 0,
                'status'      => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                'updated_at'  => Carbon::now()
            ]);

            if ($id) {
                AttributesTranslation::create($id, $this->request, $item);
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
                    Attributes::query()->where('id', $item['id'])->delete();
                    AttributesTranslation::query()->where('attribute_id', $item['id'])->delete();
                }
            }

        }

        return true;
    }


    public function getList()
    {
        $response = [];
        $values = Attributes::query()->get();

        foreach ($values as $value) {
            $response[$value->group]['group'] = $value->translation->group_title;
            $response[$value->group]['items'][] = [
                'id' => $value->id,
                'title' => $value->translation->title,
                'sort_order' => $value->sort_order
            ];
        }

        return $response;
    }
}
