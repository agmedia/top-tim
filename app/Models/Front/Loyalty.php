<?php

namespace App\Models\Front;

use App\Models\Back\Orders\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Loyalty extends Model
{

    /**
     * @var string
     */
    protected $table = 'loyalty';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];


    public static function hasLoyalty(): int
    {
        if (auth()->user()) {
            $user_id = auth()->user()->id;

            $earned = Loyalty::query()->where('user_id', $user_id)->sum('earned');
            $spent = Loyalty::query()->where('user_id', $user_id)->sum('spend');
            $has_any = intval($earned - $spent);

            if ($has_any && $has_any > 100) {
                return $has_any;
            }
        }

        return 0;
    }


    public static function calculateLoyalty(int $points = 0): int
    {
        Log::info('calculateLoyalty:: $points');
        Log::info($points);

        if (auth()->user() && $points) {
            $user_id = auth()->user()->id;

            $earned = Loyalty::query()->where('user_id', $user_id)->sum('earned');
            $spent = Loyalty::query()->where('user_id', $user_id)->sum('spend');
            $has_points = intval($earned - $spent);

            if ($has_points && $has_points > $points) {
                if ($points == 100) {
                    return 5;
                }
                if ($points == 200) {
                    return 12;
                }
            }
        }

        return 0;
    }


    public static function resolveOrder(array $cart, Order $order)
    {
        $spent = 0;

        if ($cart['loyalty']) {
            $spent = $cart['loyalty'];
        }

        return Loyalty::query()->insert([
            'user_id'      => auth()->user()->id,
            'reference_id' => $order->id,
            'target'       => 'order',
            'earned'       => intval($order->total),
            'spend'        => $spent,
            'created_at'   => now(),
            'updated_at'   => now()
        ]);
    }

}
