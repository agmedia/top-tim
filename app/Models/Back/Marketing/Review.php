<?php

namespace App\Models\Back\Marketing;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Review extends Model
{

    /**
     * @var string
     */
    protected $table = 'reviews';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    /**
     * @var string[]
     */
    //protected $appends = [];
    
    protected $casts = [
        'stars' => 'integer',
    ];

    /**
     * @var Request
     */
    protected $request;

    protected $frontSubmission = false;

    public function validateFrontRequest(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'stars' => ['required', 'integer', 'between:1,5'],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('status', 1)],
            'lang' => ['required', 'string', Rule::in(array_keys(config('laravellocalization.supportedLocales', ['hr' => []])))],
            'message' => ['required', 'string', 'max:1000'],
        ]);
        $this->request = new Request($validated);
        $this->frontSubmission = true;

        return $this;
    }


    /**
     * Validate new action Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'stars' => ['required', 'string', 'max:3'],
            'product_id' => ['required', 'string', 'max:11'],
            'lang' => ['required', 'string', 'max:2'],
            'message' => ['required', 'string', 'max:1000'],
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
        $updated = $this->update($this->createModelArray('update'));

        if ($updated) {
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
            'product_id'   => $this->request->product_id,
            'order_id'     => 0,
            'user_id'      => $this->frontSubmission ? (auth()->id() ?? 0) : 0,
            'lang'         => $this->request->lang,
            'fname'        => $this->request->name,
            'lname'        => isset($this->request->lastname) ? $this->request->lastname : '',
            'email'        => $this->request->email,
            'avatar'       => isset($this->request->avatar) ? $this->request->avatar : 'media/avatar.jpg',
            'message'      => $this->request->message,
            'stars'        => $this->request->stars ?: 5,
            'sort_order'   => isset($this->request->sort_order) ? $this->request->sort_order : 0,
            'featured'     => (isset($this->request->featured) and $this->request->featured == 'on') ? 1 : 0,
            'status'       => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
            'updated_at'   => Carbon::now()
        ];

        if ($method == 'insert') {
            $response['created_at'] = Carbon::now();
        }

        return $response;
    }


}
