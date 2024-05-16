<?php

    namespace App\Models\Front\Catalog;

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




    }

