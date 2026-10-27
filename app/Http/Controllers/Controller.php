<?php

namespace App\Http\Controllers;

use App\Models\AdexKey;
use App\Models\Feature;
use App\Models\General;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletFunding;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public  function generate_ref($title)
    {
        $code = random_int(100000, 999999);
        $me = random_int(1000, 9999);
        $app_name = env('APP_NAME');
        $ref = "|$app_name|$title|$code|adex-dev-$me";
        return $ref;
    }
    public function purchase_ref($d)
    {
        return uniqid($d);
    }
    public function system_date()
    {
        return  Carbon::now("Africa/Lagos")->toDateTimeString();
    }
}
