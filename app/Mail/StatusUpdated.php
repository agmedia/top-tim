<?php

namespace App\Mail;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderStats;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class StatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var Order
     */
    public $order;

    public $status;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->status = OrderStats::query()->where('id', $this->order->order_status_id)->first();

        return $this->subject(__('front/cart.hvala_narudzba'))
            ->view('emails.status-updated');
    }
}
