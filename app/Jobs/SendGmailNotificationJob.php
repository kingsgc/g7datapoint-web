<?php

namespace App\Jobs;

use App\Http\Controllers\MailController;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendGmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected array $userIds;
    protected string $title;
    protected string $rawMessage;

    public function __construct(array $userIds, string $title, string $rawMessage)
    {
        $this->userIds = $userIds;
        $this->title = $title;
        $this->rawMessage = $rawMessage;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = User::whereIn('id', $this->userIds)->get();
        foreach ($users as $user) {
            $adex_search = [
                '{username}',
                '{email}',
                '{fullname}',
                '{phone}',
                '{webhook}',
                '{apikey}',
                '{address}',
                '{ref}',
                '{type}',
                '{wema}',
                '{rolex}',
                '{ster}',
                '{fed}',
                '{palmpay}',
                '{otp}',
                '{user_limit}',
                '{bal}',
                '{rebal}'
            ];
            $change_adex = [
                $user->username,
                $user->email,
                $user->name,
                $user->phone,
                $user->webhook,
                $user->apikey,
                $user->address,
                $user->ref,
                $user->type,
                $user->wema,
                $user->rolex,
                $user->sterlen,
                $user->fed,
                $user->palmpay,
                $user->otp,
                $user->user_limit,
                '₦' . number_format($user->bal, 2),
                '₦' . number_format($user->refbal, 2),
            ];
            $personalizedMessage = str_replace($adex_search, $change_adex, $this->rawMessage);
            $rawTitile = str_replace($adex_search, $change_adex,  $this->title);
            $email_data = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'title' => $rawTitile,
                'sender_mail' => config('app.email'),
                'messages' => $personalizedMessage,
                'app_name' => env('APP_NAME'),
            ];
            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                MailController::send_mail($email_data, 'email.notif');
            }
        }
    }
}
