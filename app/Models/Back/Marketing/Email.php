<?php

namespace App\Models\Back\Marketing;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductCategory;
use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Email extends Model
{

    /**
     * @var string
     */
    protected $table = 'user_emails';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;


    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeUnsent(Builder $query): Builder
    {
        return $query->whereNull('sent_at');
    }


    /**
     * @param Builder $query
     * @param int     $user_id
     *
     * @return Builder
     */
    public function scopeIsSent(Builder $query, int $user_id, string $target = 'cart'): Builder
    {
        return $query->whereNull('sent_at')->where('user_id', $user_id);
    }


    /**
     * @param Builder $query
     * @param int     $user_id
     *
     * @return Builder
     */
    public function scopeFor(Builder $query, string $target = 'forgoten_cart', int $key = 1): Builder
    {
        return $query->where('target', $target)->where('key', $key);
    }


    /**
     * @param Builder $query
     * @param int     $days
     *
     * @return Builder
     */
    public function scopeSentBefore(Builder $query, int $days = 1): Builder
    {
        return $query->whereNotNull('sent_at')
                     ->where('sent_at', '>', now()->subDays($days)->endOfDay());
    }


    /**
     * Store new category.
     *
     * @return false
     */
    public function create()
    {
        $id = $this->insertGetId(
            $this->createModelArray()
        );

        if ($id) {
            return $this->find($id);
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
            'user_id'      => 0,
            'lang'         => 'hr',
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


    public static function sendForgotenCartEmails()
    {
        $unsent_emails = collect();
        $first_sending = Cart::query()->notOlderThan(30)->get();

        foreach ($first_sending as $item) {
            $is_sent = Email::query()->isSent($item->user_id)->for('forgoten_cart', 1)->first();

            if ( ! $is_sent) {
                $unsent_emails->push($item);
            }
        }

        dd($unsent_emails->toArray());
    }

}
