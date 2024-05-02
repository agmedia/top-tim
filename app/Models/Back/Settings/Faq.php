<?php

namespace App\Models\Back\Settings;

use App\Http\Controllers\FaqTranslation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Faq extends Model
{

    /**
     * @var string
     */
    protected $table = 'faq';

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
            return $this->hasOne(FaqTranslation::class, 'faq_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(FaqTranslation::class, 'faq_id');
        }

        return $this->hasOne(FaqTranslation::class, 'faq_id')->where('lang', $this->locale);
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
            'title'       => 'required',
            'description' => 'required'
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
            FaqTranslation::create($id, $this->request);

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
            FaqTranslation::edit($id, $this->request);

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
}
