<?php

namespace App\Models\Front;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Newsletter extends Model
{

    /**
     * @var string
     */
    protected $table = 'newsletter';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    /**
     * @var Request
     */
    protected $request;


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

            'email' => ['required', 'string', 'email', 'max:255']

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
            'email'        => $this->request->email,
            'updated_at'   => Carbon::now()
        ];

        if ($method == 'insert') {
            $response['created_at'] = Carbon::now();
        }

        return $response;
    }

}
