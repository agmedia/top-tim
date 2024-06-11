<?php

namespace App\Models\Back\Settings;

use App\Models\Back\Catalog\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class SizeGuide extends Model
{

    /**
     * @var string
     */
    protected $table = 'sizeguide';

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
            return $this->hasOne(SizeGuideTranslation::class, 'sizeguide_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(SizeGuideTranslation::class, 'sizeguide_id');
        }

        return $this->hasOne(SizeGuideTranslation::class, 'sizeguide_id')->where('lang', $this->locale);
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
            'title.*'       => 'required',
            'image' => 'required'
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
            SizeGuideTranslation::create($id, $this->request);

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
            SizeGuideTranslation::edit($this->id, $this->request);

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
        $response = [
            'group'       => null,
            'sort_order'  => 0,
            'status'      => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'updated_at'  => Carbon::now()
        ];

        if ($method == 'insert') {
            $response['created_at'] = Carbon::now();
        }

        return $response;
    }

    public function resolveImage(SizeGuide $sizeguide)
    {
        if ($this->request->hasFile('image')) {
            $img = Image::make($this->request->image);
            $str = $sizeguide->id . '/' . Str::slug($sizeguide->translation(current_locale())->title) . '-' . time() . '.';

            $path = $str . 'jpg';
            Storage::disk('sizeguide')->put($path, $img->encode('jpg'));

            $path_webp = $str . 'webp';
            Storage::disk('sizeguide')->put($path_webp, $img->encode('webp'));

            // Thumb creation
            $path_thumb = $sizeguide->id . '/' . Str::slug($sizeguide->translation(current_locale())->title) . '-' . time() . '-thumb.';
            $canvas = Image::canvas(400, 400, '#ffffff');

            $img = $img->resize(null, 400, function ($constraint) {
                $constraint->aspectRatio();
            });

            $canvas->insert($img, 'center');

            $path_webp_thumb = $path_thumb . 'webp';
            Storage::disk('sizeguide')->put($path_webp_thumb, $canvas->encode('webp'));

            return $sizeguide->update([
                'image' => config('filesystems.disks.sizeguide.url') . $path
            ]);
        }

        return false;
    }
}
