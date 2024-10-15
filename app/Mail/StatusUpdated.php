<?php

namespace App\Mail;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\OrderStats;
use App\Models\Back\Settings\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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
       // $this->status = Settings::get('order', 'statuses')->where('id', $this->order->order_status_id)->first();

        $this->status = Settings::get('order', 'statuses');
        Log::info($this->status);

        Log::info($this->order->order_status_id);


        return $this->subject(__('front/cart.hvala_narudzba'))
            ->view('emails.status-updated');
    }
}
