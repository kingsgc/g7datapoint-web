<?php

namespace App\Jobs;

use App\Models\Notif;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\SendFCMNotification;

class SystemNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    protected array $userIds;
    protected string $title;
    protected string $rawMessage;

    public function __construct(array $userIds, string $title, string $rawMessage)
    {
        $this->userIds = $userIds;
        $this->title = $title;
        $this->rawMessage = $rawMessage;
    }

    public function handle(): void
    {
        $users = User::whereIn('id', $this->userIds)->get();
        $jobs = [];

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
            $cleanMessage = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($personalizedMessage, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));

            // Save to database
            Notif::create([
                'username' => $user->username,
                'title' => $this->title,
                'message' => $cleanMessage,
                'date' => now(),
                'adex' => 0,
                'user_id' => $user->id
            ]);

            // Add FCM job to batch if token is available
            if (!is_null($user->app_token)) {
                $jobs[] = new SendFCMNotification($user, $this->title, $cleanMessage);
            }
        }

        if (!empty($jobs)) {
            Bus::batch($jobs)->name('Batch FCM Notifications')->allowFailures()->dispatch();
        }
    }
}
