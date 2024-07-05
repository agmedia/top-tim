<?php

namespace App\Models\Back\Marketing;

use App\Models\Back\Settings\PageTranslation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class Blog extends Model
{

    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'pages';

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
            return $this->hasOne(PageTranslation::class, 'page_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(PageTranslation::class, 'page_id');
        }

        return $this->hasOne(PageTranslation::class, 'page_id')->where('lang', $this->locale);
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
            PageTranslation::create($id, $this->request);

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
            PageTranslation::edit($this->id, $this->request);

            return $this;
        }

        return false;
    }


    /**
     * @param Category $category
     *
     * @return bool
     */
    public function resolveImage(Blog $blog)
    {
        if ($this->request->hasFile('image')) {
            $img = Image::make($this->request->image);
            $str = $blog->id . '/' . Str::slug($blog->title) . '-' . time() . '.';

            $path = $str . 'jpg';
            Storage::disk('blog')->put($path, $img->encode('jpg'));

            $path_webp = $str . 'webp';
            Storage::disk('blog')->put($path_webp, $img->encode('webp'));

            // Thumb creation
            $path_thumb = $blog->id . '/' . Str::slug($blog->title) . '-' . time() . '-thumb.';
            $canvas = Image::canvas(400, 250, '#ffffff');

            $img = $img->resize(null, 250, function ($constraint) {
                $constraint->aspectRatio();
            });

            $canvas->insert($img, 'center');

            $path_webp_thumb = $path_thumb . 'webp';
            Storage::disk('blog')->put($path_webp_thumb, $canvas->encode('webp'));

            return $blog->update([
                'image' => config('filesystems.disks.blog.url') . $path
            ]);
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
            'category_id'       => 3,
            'group'             => 'blog',
            'publish_date'      => null,
            'status'            => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'updated_at'        => Carbon::now()
        ];

        if ($method == 'insert') {
            $response['created_at'] = Carbon::now();
        }

        return $response;
    }
}
