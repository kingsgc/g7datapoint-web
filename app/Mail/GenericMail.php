<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $template;

    public function __construct(array $data, string $template)
    {
        $this->data = $data;
        $this->template = $template;
    }

    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'), config('app.name'))
            ->subject($this->data['title'] ?? 'Notification')
            ->view($this->template)
            ->with($this->data);
    }
}
