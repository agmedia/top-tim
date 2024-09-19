<?php

namespace App\Models\Back\Catalog;

use App\Http\Controllers\Back\Catalog\CategoryController;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class Category extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'categories';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var string
     */
    protected $locale = '';

    /**
     * @var Request
     */
    protected $request;


    /**
     * Gallery constructor.
     *
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
            return $this->hasOne(CategoryTranslation::class, 'category_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(CategoryTranslation::class, 'category_id');
        }

        return $this->hasOne(CategoryTranslation::class, 'category_id')->where('lang', $this->locale);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subcategories()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function products()
    {
        return $this->hasManyThrough(Product::class, ProductCategory::class, 'category_id', 'id', 'id', 'product_id');
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }


    /**
     * @param Builder $query
     * @param string  $group
     *
     * @return Builder
     */
    public function scopeTopList(Builder $query, string $group = ''): Builder
    {
        if ( ! empty($group)) {
            return $query->where('group', $group)->where('parent_id', '==', 0);
        }

        return $query->where('parent_id', '==', 0);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeGroups(Builder $query): Builder
    {
        return $query->groupBy('group');
    }


    /**
     * @param bool $full
     *
     * @return Collection
     */
    public function getList(bool $full = true): Collection
    {
        $categories = collect();

        $groups = $this->groups()->pluck('group');

        foreach ($groups as $group) {
            if ($full) {
                $cats = $this->where('group', $group)->where('parent_id', 0)->with('subcategories', 'translation')->withCount('products')->get();
            } else {
                $cats = [];
                $fill = $this->where('group', $group)->where('parent_id', 0)->with('subcategories', 'translation')->withCount('products')->get();

                foreach ($fill as $cat) {
                    $cats[$cat->id] = ['title' => $cat->translation->title];

                    if ($cat->subcategories) {
                        $subcats = [];

                        foreach ($cat->subcategories as $subcategory) {
                            $subcats[$subcategory->id] = ['title' => $subcategory->translation->title];
                        }
                    }

                    $cats[$cat->id]['subs'] = $subcats;
                }
            }

            $categories->put($group, $cats);
        }

        return $categories;
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
            'title.*' => 'required'
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
        $id = $this->insertGetId($this->createModelArray());

        if ($id) {
            CategoryTranslation::create($id, $this->request);

            return $this->find($id);
        }

        return false;
    }


    /**
     * @param Category $category
     *
     * @return false
     */
    public function edit()
    {
        $id = $this->update($this->createModelArray('update'));

        if ($id) {
            CategoryTranslation::edit($this->id, $this->request);

            return $this;
        }

        return false;
    }


    /**
     * @param string $method
     *
     * @return array
     */
    private function createModelArray(string $method = 'insert'): array
    {
        $parent = $this->request->parent ?: 0;
        $group  = isset($this->request->group) ? $this->request->group : 0;

        if ($parent) {
            $topcat = $this->where('id', $parent)->first();
            $group  = $topcat->group;
        }

        $response = [
            'parent_id'  => $parent,
            'group'      => $group,
            'sort_order' => $this->request->sort_order,
            'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'updated_at' => Carbon::now()
        ];

        if ($method == 'insert') {
            $response['created_at'] = Carbon::now();
        }

        return $response;
    }


    /**
     * @param Category $category
     *
     * @return bool
     */
    public function resolveImage(Category $category)
    {
        if ($this->request->hasFile('image')) {
            $img = Image::make($this->request->image);
            $str = $category->id . '/' . Str::slug($category->translation(current_locale())->title) . '-' . time() . '.';

            $path = $str . 'jpg';
            Storage::disk('category')->put($path, $img->encode('jpg'));

            $path_webp = $str . 'webp';
            Storage::disk('category')->put($path_webp, $img->encode('webp'));

            // Thumb creation
            $path_thumb = $category->id . '/' . Str::slug($category->translation(current_locale())->title) . '-' . time() . '-thumb.';
            $canvas = Image::canvas(400, 400, '#ffffff');

            $img = $img->resize(null, 400, function ($constraint) {
                $constraint->aspectRatio();
            });

            $canvas->insert($img, 'center');

            $path_webp_thumb = $path_thumb . 'webp';
            Storage::disk('category')->put($path_webp_thumb, $canvas->encode('webp'));

            return $category->update([
                'image' => config('filesystems.disks.category.url') . $path
            ]);
        }

        return false;
    }


    public static function getParents(): array
    {
        $response[0] = 'Glavna Kategorija';

        $tops = self::query()->where('parent_id', 0)->with('translation')->get();

        foreach ($tops as $top) {
            if ($top->translation) {
                $response[$top->id] = $top->translation->title;
            }
        }

        return $response;
    }

}
