<?php

namespace App\Console\Commands;

use App\Models\Back\Marketing\Email;
use Illuminate\Console\Command;

class SendForgotenCartEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:forgoten_cart_emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails to users with forgoten shoping carts.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return Email::sendForgotenCartEmails();
    }
}
