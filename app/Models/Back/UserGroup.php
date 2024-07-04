<?php

namespace App\Models\Back;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserGroup extends Model
{

    /**
     * @var string
     */
    protected $table = 'user_group';

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
            return $this->hasOne(UserGroupTranslation::class, 'user_group_id')->where('lang', $lang)->first();
        }

        if ($all) {
            return $this->hasMany(UserGroupTranslation::class, 'user_group_id');
        }

        return $this->hasOne(UserGroupTranslation::class, 'user_group_id')->where('lang', $this->locale);
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
        $parent = $this->request->parent ?: 0;

        foreach ($this->request->input('item') as $item) {
            $id = $this->insertGetId([
                'parent_id'  => $parent,
                'sort_order' => $item['sort_order'] ?? 0,
                'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                'updated_at' => Carbon::now()
            ]);

            if ($id) {
                UserGroupTranslation::create($id, $this->request, $item);
            } else {
                return false;
            }
        }

        return $this->find($id);
    }



    /**
     * @return array
     */
    public function getList()
    {
        $response = [];
        $values   = UserGroup::query()->get();

        foreach ($values as $value) {
            $response['items'][] = [
                'id'         => $value->id,
                'title'      => $value->translation->title,
                'status' => $value->status
            ];
        }

        return $response;
    }



}
