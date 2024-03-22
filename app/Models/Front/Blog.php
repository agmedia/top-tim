<?php

namespace App\Models\Front;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Blog extends Model
{

    /**
     * @var string
     */
    protected $table = 'pages';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var string[]
     */
    protected $appends = ['thumb'];

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
     * @param $locale
     *
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    public function getLocalizedRouteKey($locale)
    {
        return $this->translation($locale)->slug;
    }


    /**
     * @param $value
     * @param $field
     *
     * @return Model|never|null
     */
    public function resolveRouteBinding($value, $field = NULL)
    {
        return static::whereHas('translation', function ($query) use ($value) {
            $query->where('slug', $value);
        })->first() ?? abort(404);
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
     * @return string
     */
    public function getTitleAttribute()
    {
        return $this->translation->title;
    }


    /**
     * @return string
     */
    public function getDescriptionAttribute()
    {
        return $this->translation->description;
    }



    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }


    /**
     * @param $value
     *
     * @return array|string|string[]
     */
    public function getImageAttribute($value)
    {
        return config('settings.images_domain') . str_replace('.jpg', '.webp', $value);
    }


    /**
     * @param $value
     *
     * @return array|string|string[]
     */
    public function getThumbAttribute($value)
    {
        return config('settings.images_domain') . str_replace('.jpg', '-thumb.webp', $value);
    }


    /**
     *
     */
    protected static function booted()
    {
        static::addGlobalScope('blogs', function (Builder $builder) {
            $builder->where('group', 'blog');
        });
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1)->orderBy('created_at', 'desc');
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeLast(Builder $query, $count = 9): Builder
    {
        return $query->orderBy('updated_at', 'desc')->limit($count);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopePopular(Builder $query, $count = 9): Builder
    {
        return $query->orderBy('viewed', 'desc')->limit($count);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeFeatured(Builder $query, $count = 9): Builder
    {
        return $query->where('featured', 1)->orderBy('updated_at', 'desc')->limit($count);
    }
}
