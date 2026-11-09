<?php

namespace App\Services;

use App\Mail\OrderReceived;
use App\Mail\OrderSent;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderHistory;
use App\Models\Back\Orders\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckoutCompletion
{
    public const HISTORY_MARKER = 'Checkout completed.';

    /** Only call after the gateway receipt has been authenticated and matched. */
    public function paid(int $id, string $provider, array $receipt): bool
    {
        return DB::transaction(function () use ($id, $provider, $receipt) {
            $order = Order::query()->lockForUpdate()->find($id);

            if (! $order || $order->payment_code !== $provider || $order->products()->count() === 0) {
                return false;
            }

            if ($this->completed($order)) {
                return in_array((int) $order->order_status_id, [
                    config('settings.order.status.paid'), config('settings.order.status.send'),
                ], true) && Transaction::where('order_id', $id)->where('success', 1)
                    ->where('pg_order_id', $receipt['pg_order_id'])->exists();
            }

            if (! in_array((int) $order->order_status_id, [
                config('settings.order.status.unfinished'), config('settings.order.status.declined'),
            ], true) || ! $this->sameAmount($receipt['amount'], $order->total)) {
                return false;
            }

            $order->update(['order_status_id' => config('settings.order.status.paid')]);
            Transaction::insert(array_merge($receipt, [
                'order_id' => $id, 'success' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]));
            $this->complete($order);

            return true;
        });
    }

    public function rollback(int $id, string $reference, $amount): bool
    {
        return DB::transaction(function () use ($id, $reference, $amount) {
            $order = Order::query()->lockForUpdate()->find($id);
            if (! $order || $order->payment_code !== 'mypos' || ! $this->sameAmount($amount, $order->total)) {
                return false;
            }

            $receipt = Transaction::where('order_id', $id)->where('success', 1)
                ->where('pg_order_id', $reference)->first();
            if (! $receipt || ! $this->sameAmount($amount, $receipt->amount)) {
                return false;
            }

            $marker = 'myPOS payment reversed: ' . $reference;
            if (! OrderHistory::where('order_id', $id)->where('comment', $marker)->exists()) {
                $order->update(['order_status_id' => config('settings.order.status.canceled')]);
                OrderHistory::insert([
                    'order_id' => $id, 'user_id' => 0, 'comment' => $marker,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return true;
        });
    }

    public function offline(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $order = Order::query()->lockForUpdate()->find($id);

            if (! $order || ! in_array($order->payment_code, ['bank', 'cod', 'pickup'], true)
                || $order->products()->count() === 0) {
                return false;
            }

            if ($this->completed($order)) {
                return true;
            }

            if ((int) $order->order_status_id !== config('settings.order.status.unfinished')) {
                return false;
            }

            $order->update(['order_status_id' => config('settings.order.status.new')]);
            $this->complete($order);

            return true;
        });
    }

    public function sameAmount($amount, $expected): bool
    {
        return is_scalar($amount) && preg_match('/^\d+(?:[.,]\d{1,2})?$/D', (string) $amount)
            && number_format((float) str_replace(',', '.', (string) $amount), 2, '.', '')
                === number_format((float) $expected, 2, '.', '');
    }

    private function completed(Order $order): bool
    {
        return OrderHistory::where('order_id', $order->id)->where('comment', self::HISTORY_MARKER)->exists();
    }

    /** The caller holds the order row lock throughout stock, history and receipt changes. */
    private function complete(Order $order): void
    {
        $order->decreaseItems($order->products);
        OrderHistory::insert([
            'order_id' => $order->id, 'user_id' => $order->user_id ?: 0,
            'comment' => self::HISTORY_MARKER, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::afterCommit(function () use ($order) {
            dispatch(function () use ($order) {
                Mail::to(config('mail.admin'))->send(new OrderReceived($order));
                Mail::to($order->payment_email)->send(new OrderSent($order));
            })->afterResponse();
        });
    }
}
