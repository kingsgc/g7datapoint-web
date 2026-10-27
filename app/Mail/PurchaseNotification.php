<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $email_data;

    public function __construct(array $email_data)
    {
        $this->email_data = $email_data;
    }

    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'), $this->email_data['app_name'])
            ->subject($this->email_data['title'] ?? 'Purchase Notification')
            ->view('email.purchase')
            ->with($this->email_data);
    }
}
