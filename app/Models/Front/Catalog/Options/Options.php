<?php

namespace  App\Models\Front\Catalog\Options;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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



    public function getList()
    {
        $response = [];
        $values = Options::query()->get();

        foreach ($values as $value) {
            $response[$value->group]['group'] = $value->translation->group_title;
            $response[$value->group]['items'][] = [
                'id' => $value->id,
                'title' => $value->translation->title,
                'value' => $value->color,
                'value_opt'       => $value->color_opt,
                'sort_order' => $value->sort_order
            ];
        }

        return $response;
    }
}
