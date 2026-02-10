<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportFullExcelFinished extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var bool
     */
    public $success;

    /**
     * @var string
     */
    public $summary;

    /**
     * @var string
     */
    public $filePath;

    /**
     * Create a new message instance.
     *
     * @param bool   $success
     * @param string $summary
     * @param string $filePath
     */
    public function __construct(bool $success, string $summary, string $filePath)
    {
        $this->success = $success;
        $this->summary = $summary;
        $this->filePath = $filePath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->success
            ? '[TopTim] Import Full Excel završio'
            : '[TopTim] Import Full Excel greška';

        return $this->subject($subject)
            ->view('emails.import-full-excel-finished');
    }
}

