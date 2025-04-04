<?php

namespace App\Models;

use App\Models\Back\UserGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{

    /**
     * @var string
     */
    protected $table = 'user_details';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function group()
    {
        return $this->hasOne(UserGroup::class, 'id', 'user_group_id');
    }


    /**
     * @param $products
     * @param $order_id
     *
     * @return bool
     */
    public static function storeData($request, $user_id)
    {
        return self::insertGetId([
            'user_id'    => $user_id,
            'fname'      => isset($request->user_fname) ? $request->user_fname : $request->user_name,
            'lname'      => $request->user_lname,
            'address'    => $request->user_address,
            'zip'        => $request->user_zip,
            'city'       => $request->user_city,
            'phone'      => $request->user_phone,
            'bio'        => $request->user_description,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }


    /**
     * @param $products
     * @param $order_id
     *
     * @return bool
     */
    public static function updateData($request, $user_id)
    {
        return self::where('user_id', $user_id)->update([
            'fname'      => isset($request->user_fname) ? $request->user_fname : $request->user_name,
            'lname'      => $request->user_lname,
            'address'    => $request->user_address,
            'zip'        => $request->user_zip,
            'city'       => $request->user_city,
            'phone'      => $request->user_phone,
            'bio'        => $request->user_description,
            'updated_at' => Carbon::now()
        ]);
    }


    /**
     * @param $products
     * @param $order_id
     *
     * @return bool
     */
    public static function updateCustomerData($request, $user_id)
    {
        return self::where('user_id', $user_id)->update([
            'fname'      => $request->fname ? $request->fname : $request->name,
            'lname'      => $request->lname,
            'address'    => $request->address,
            'zip'        => $request->zip,
            'city'       => $request->city,
            'phone'      => $request->phone,
            'bio'        => $request->bio,
            'company'    => $request->company,
            'oib'        => $request->oib,
            'updated_at' => Carbon::now()
        ]);
    }
}
