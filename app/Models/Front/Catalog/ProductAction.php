<?php

namespace App\Models\Front\Catalog;

use App\Models\Front\Catalog\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductAction extends Model
{

    /**
     * @var string
     */
    protected $table = 'product_actions';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

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

        $this->locale = session('locale');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }


    /**
     * @param      $lang
     * @param bool $all
     *
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function translation($lang = null, bool $all = false)
    {
        if ($lang) {
            return $this->hasOne(ProductActionTranslation::class, 'product_action_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(ProductActionTranslation::class, 'product_action_id');
        }

        return $this->hasOne(ProductActionTranslation::class, 'product_action_id')->where('lang', $this->locale);
    }


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', '=', 1)
            ->where(function (Builder $query) {
                $query->where('date_start', '<', Carbon::now())
                      ->orWhere('date_start', '=', null);
            })
            ->where(function (Builder $query) {
                $query->where('date_end', '>', Carbon::now())
                      ->orWhere('date_end', '=', null);
            });
    }
}
