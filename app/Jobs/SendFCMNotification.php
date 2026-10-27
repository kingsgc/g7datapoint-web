<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batchable;

class SendFCMNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public User $user;
    public string $title;
    public string $message;

    public function __construct(User $user, string $title, string $message)
    {
        $this->user = $user;
        $this->title = $title;
        $this->message = $message;
    }

    public function handle(): void
    {
        if (!$this->user->app_token) {
            return;
        }

        try {
            $messaging = Firebase::messaging();

            $fcmMessage = CloudMessage::withTarget('token', $this->user->app_token)
                ->withNotification(Notification::create($this->title, $this->message));
            $messaging->send($fcmMessage);
            // Log::info("✅ FCM sent to user ID {$this->user->id}");
        } catch (\Exception $e) {
            //Log::error("❌ FCM error for user ID {$this->user->id}: " . $e->getMessage());
        }
    }
}
