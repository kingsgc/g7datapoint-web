<?php

namespace App\Jobs;

use App\Models\OtherApi;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $userIds;
    protected string $title;
    protected string $rawMessage;

    /**
     * Create a new job instance.
     */
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
        $adex_api = OtherApi::first();

        $requests = [];

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

            // Queue up a request to be sent in the pool
            $requests[] = fn($pool) => $pool->asForm()->post('https://sms.hollatags.com/api/send/', [
                'user' => $adex_api->hollatag_username,
                'pass' => $adex_api->hollatag_password,
                'from' => $this->title,
                'to'   => $user->phone,
                'msg'  => $personalizedMessage,
                'type' => 0,
            ]);
        }

        // Execute all requests concurrently using Http::pool
        $responses = Http::pool(fn($pool) => array_map(fn($request) => $request($pool), $requests));

        // Log responses
        // foreach ($responses as $index => $response) {
        //     $userId = $this->userIds[$index] ?? null;
        //     if ($response->successful()) {
        //         // Log::info("✅ SMS sent to user ID {$userId}: " . json_encode($response->json()));
        //     } else {
        //         Log::error("❌ Failed SMS to user ID {$userId}: " . $response->body());
        //     }
        // }
    }
}
