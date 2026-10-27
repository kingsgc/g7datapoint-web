<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\GenericMail;

class MailController extends Controller
{
    public static function send_mail($user_data, $template, $queue = false)
    {
        try {
            $mailable = new GenericMail($user_data, $template);
            if ($queue) {
                Mail::to($user_data['email'])->queue($mailable); // queued
            } else {
                Mail::to($user_data['email'])->send($mailable);  // immediate
            }
        } catch (\Exception $e) {
            \Log::error("Email Error: " . $e->getMessage());
        }
    }
}
