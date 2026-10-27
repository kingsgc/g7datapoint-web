<?php
use App\Http\Controllers\API\AppController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Banks;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\NewStock;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\PlanController;
use App\Http\Controllers\API\SecureController;
use App\Http\Controllers\API\Trans;
use App\Http\Controllers\API\TransactionCalculator;
use App\Http\Controllers\API\WebhookController;
use App\Http\Controllers\Purchase\AccessUser;
use App\Http\Controllers\Purchase\AirtimeCash;
use App\Http\Controllers\Purchase\AirtimePurchase;
use App\Http\Controllers\Purchase\BillPurchase;
use App\Http\Controllers\Purchase\BonusTransfer;
use App\Http\Controllers\Purchase\BulksmsPurchase;
use App\Http\Controllers\Purchase\CablePurchase;
use App\Http\Controllers\Purchase\DataPurchase;
use App\Http\Controllers\Purchase\ExamPurchase;
use App\Http\Controllers\Purchase\IUCvad;
use App\Http\Controllers\Purchase\MeterVerify;
use App\Http\Controllers\APP\Auth;
use App\Http\Controllers\Purchase\DataCard;
use App\Http\Controllers\Purchase\RechargeCard;
use App\Http\Controllers\API\WithdrawalController;

Route::middleware(['webSecure', 'secured'])->group(function () {
    Route::get('account/my-account/{id}', [AuthController::class, 'account']);
    Route::get('check/banks/user/gstar/{id}/secure/this/site/here', [Banks::class, 'GetBanksArray']);
    Route::get('website/app/network', [AppController::class, 'SystemNetwork']);
    Route::post('website/{id}/affliate/user', [AppController::class, 'buildWebsite']);
    Route::get('upgrade/awuf/{id}/user', [AppController::class, 'AwufPackage']);
    Route::get('upgrade/agent/{id}/user', [AppController::class, 'AgentPackage']);
    Route::get('system/notification/user/{id}/request/user', [AppController::class, 'UserNotif']);
    Route::get('clear/notification/clear/all/{id}/by/user', [AppController::class, 'ClearNotifUser']);
    Route::get('total/data/purchase/{id}/secure', [SecureController::class, 'DataPurchased']);
    Route::post('get/data/plans/{id}/adex', [PlanController::class, 'DataPlan']);
    Route::get('website/app/{id}/dataplan', [PlanController::class, 'DataList']);
    Route::post('manualpayment/adex/secure/{id}/secure', [PaymentController::class, 'BankTransfer']);
    Route::post('dynamic-account-number-here/{id}/adex/secure', [PaymentController::class, 'DynamicAccount']);
    Route::post('edituser/adex/secure/{id}/secure', [SecureController::class, 'UserProfile']);
    Route::get('user/accountdetails/wallet/{id}/secure/adex', [SecureController::class, 'UsersAccountDetails']);
    Route::post('accountdetails/adex/secure/{id}/secure', [SecureController::class, 'UserAccountDetails']);
    Route::get('user/stock/wallet/{id}/secure/adex', [SecureController::class, 'UserStock']);
    Route::post('change/password/by/user/adex/{id}/now', [SecureController::class, 'ResetPasswordUser']);
    Route::post('change/pin/by/user/adex/{id}/now', [SecureController::class, 'ChangePin']);
    Route::post('create/newpin/by/user/adex/{id}/now', [SecureController::class, 'CreatePin']);
    Route::post('get/data_card_plans/{id}/adex', [NewStock::class, 'UserDataCardPlan']);
    Route::post('get/recharge_card_plans/{id}/adex', [NewStock::class, 'UserRechargeCardPlan']);
     Route::get('website/app/{id}/recharge_card_pan', [PlanController::class, 'RechargeCard']);
     Route::get('website/app/{id}/data_card_pan', [PlanController::class, 'DataCard']);

    //transaction
    Route::get('system/all/history/adex/{id}/secure', [Trans::class, 'AllHistoryUser']);
    Route::get('system/all/datatrans/adex/{id}/secure', [Trans::class, 'AllDataHistoryUser']);
    Route::get('system/all/stock/trans/adex/{id}/secure', [Trans::class, 'AllStockHistoryUser']);
    Route::get('system/all/airtime/trans/adex/{id}/secure', [Trans::class, 'AllAirtimeUser']);
    Route::get('system/all/trans/{id}/secure', [Trans::class, 'UserTrans']);
    Route::get('system/all/deposit/trans/adex/{id}/secure', [Trans::class, 'AllDepositHistory']);
    Route::get('all/data_recharge_cards/{id}/secure', [Trans::class, 'DataRechardPrint']);
    Route::post('user/calculator/{id}/adex/secure', [TransactionCalculator::class, 'User']);
    Route::get('system/user/stockbalance/{id}/secure', [SecureController::class, 'StockBalance']);
    Route::post('user/edit/stockvending/{id}/adex/secure', [SecureController::class, 'UserEditStock']);
    Route::get('manual/trans/{id}/secure', [Trans::class, 'ManualTransfer']);
    
});

Route::middleware('webSecure')->group(function () {
    Route::post('login/verify/user', [AuthController::class, 'login']);
    Route::get('website/app/setting', [AppController::class, 'system']);
    Route::get('data/plan-types/{id}', [AppController::class, 'PlanType']);
    Route::get('/verify/network/{id}/adex/system', [AppController::class, 'checkNetworkType']);
    Route::get('website/app/cable/lock', [AppController::class, 'CableName']);
    Route::get('cable/plan/{id}/adex/system', [PlanController::class, 'CablePlan']);
    Route::get('cable/charges/{id}/admin', [PlanController::class, 'CableCharges']);
    Route::get('bill/charges/{id}/admin', [AppController::class, 'BillCal']);
    Route::get('bulksms/cal/admin', [AppController::class, 'BulksmsCal']);
    Route::get('resultprice/admin/secure', [AppController::class, 'ResultPrice']);
    Route::get('website/app/disco', [AppController::class, 'DiscoList']);
    Route::get('website/app/cableplan', [PlanController::class, 'CableList']);
    Route::get('website/app/exam', [PlanController::class, 'ExamList']);
    Route::post('airtimecash/discount/admin', [AppController::class, 'AirtimeCash']);
    Route::get('data/trans/{id}/secure', [Trans::class, "DataTrans"]);
    Route::get('website/app/bill/list', [AppController::class, 'DiscoList']);
    Route::get('data_card/trans/{id}/secure', [Trans::class, 'DataCardInvoice']);
    Route::get('deposit/trans/{id}/secure', [Trans::class, 'DepositTrans']);
    Route::get('airtime/trans/{id}/secure', [Trans::class, 'AirtimeTrans']);
    Route::get('cable/trans/{id}/secure', [Trans::class, 'CableTrans']);
    Route::get('bill/trans/{id}/secure', [Trans::class, 'BillTrans']);
    Route::get('bulksms/trans/{id}/secure', [Trans::class, 'BulkSMSTrans']);
    Route::get('data_card/trans/{id}/secure/sucess', [Trans::class, 'DataCardSuccess']);
    Route::get('airtimecash/trans/{id}/secure', [Trans::class, 'AirtimeCashTrans']);
    Route::get('resultchecker/trans/{id}/secure', [Trans::class, 'ResultCheckerTrans']);
    Route::get('recharge_card/trans/{id}/secure', [Trans::class, 'RechargeCardProcess']);
    Route::get('recharge_card/trans/{id}/secure/sucess', [Trans::class, 'RechargeCardPrint']);
    Route::get('system/app/softwarwe', [SecureController::class, 'SOFTWARE']);
   

    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify/user/accounct', [AuthController::class, 'verify']);
    Route::post('verify/user/accounct/securedpin/otp', [AuthController::class, 'verifyPIN']);
    Route::get('user/resend/{id}/otp', [AuthController::class, 'resendOtp']);
    Route::post('reset/mypassword', [SecureController::class, 'ResetPassword']);
    Route::post('change/mypassword/{id}/secure', [SecureController::class, 'ChangePPassword']);

    // list data plan 
    Route::get('website/plan', [PlanController::class, 'HomeData']);

    // app route
});
// api endpoint for users
Route::post('user', [AccessUser::class, 'Generate']);
Route::middleware(['services'])->group(function () {
    // services
    Route::post('data', [DataPurchase::class, 'BuyData']);
    Route::post('topup', [AirtimePurchase::class, 'BuyAirtime']);
    Route::get('cable/cable-validation', [IUCvad::class, 'IUC']);
    Route::post('cable', [CablePurchase::class, 'BuyCable']);
    Route::get('bill/bill-validation', [MeterVerify::class, 'Check']);
    Route::post('bill', [BillPurchase::class, 'Buy']);
    Route::post('cash', [AirtimeCash::class, 'Convert']);
    Route::post('bulksms', [BulksmsPurchase::class, 'Buy']);
    Route::post('transferwallet', [BonusTransfer::class, 'Convert']);
    Route::post('exam', [ExamPurchase::class, 'ExamPurchase']);
    Route::post('data_card', [DataCard::class, 'DataCardPurchase']);
    Route::post('recharge_card', [RechargeCard::class, 'RechargeCardPurchase']);

    // this work with aps
    Route::post('update-kyc-here/adex/secure', [PaymentController::class, 'UpdateKYC']);
    
});
// fund
// Route::any('monnify_webhook_new_fund_g_developer_errfgddgdhe', [PaymentController::class, 'MonnifyWebhook']);
Route::any('callback/simserver', [WebhookController::class, 'Simserver']);
Route::any('xixapay/calling/payment/secure/techboy/6392', [PaymentController::class, 'Xixapay']);
Route::any('quicklysim/connect/secure/hssh/techboy/8637', [WebhookController::class, 'Quicklysim']);
Route::any('paystack/adex/secure/{id}/secure', [PaymentController::class, 'Paystack']);
Route::post('adex/webhook/secure', [WebhookController::class, 'AdexWebhook']);

// app route
Route::middleware(['webSecure'])->group(function () {
    Route::post('app/adex/secure/login', [Auth::class, 'AppLogin']);
    Route::post('app/adex/signup', [Auth::class, 'SignUp']);
    Route::get('app/adex/setting', [Auth::class, 'AppGeneral']);
    Route::get('app/cable_bill', [Auth::class, 'CableBillID']);
    Route::post('app/cable_plan/load', [Auth::class, 'CablePlan']);
    Route::get('app/carousel-data', [Auth::class, 'carouselData']);
    Route::middleware(['services'])->group(function () {
        Route::get('app/network', [Auth::class, 'Network']);
        Route::post('app/verify/transaction-pin', [Auth::class, 'TransactionPin']);
        Route::get('app/recentTransaction', [Auth::class, 'recentTransaction']);
        Route::post('app/clear/notification/here', [Auth::class, 'ClearNotification']);
        Route::post('app/mark-notification-as-read', [Auth::class, 'ReadNotification']);
        Route::post('app/system_notification_here', [Auth::class, 'AppSystemNotification']);
        Route::post('app/otp_transaction_pin', [Auth::class, 'SendOtp']);
        Route::post('app/secure/check/login/details', [Auth::class, 'APPLOAD']);
        Route::post('app/profile_image', [Auth::class, 'ProfileImage']);
        Route::post('app/finger/adex/login', [Auth::class, 'FingerPrint']);
        Route::post('app/data_plan/{id}/load', [Auth::class, 'DataPlans']);
        Route::get('app/network_type/{id}/check', [Auth::class, 'NetworkType']);
        Route::post('app/adex/resend/otp', [Auth::class, 'ResendOtp']);
        Route::post('app/adex/verify/otp', [Auth::class, 'AppVerify']);
        Route::post('app/price', [Auth::class, 'PriceList']);
        Route::post('app/transaction', [Auth::class, 'Transaction']);
        Route::post('app/notification', [Auth::class, 'Notification']);
        Route::post('app/complete_profile', [Auth::class, 'CompleteProfile']);
        Route::post('app/complete_pin', [Auth::class, 'NewPin']);
        Route::post('app/deposit/transaction', [Auth::class, 'DepositTransaction']);
        Route::post('app/transaction/details', [Auth::class, 'TransactionInvoice']);
        Route::post('app/transaction_history_adex_doing', [Auth::class, 'TransactionHistoryAdex']);
        Route::post('app/data_card_plan', [Auth::class, 'DataCardPlans']);
        Route::post('app/recharge_card_plan', [Auth::class, 'RechargeCardPlans']);
        Route::post('app/delete_account_adex', [Auth::class, 'notifyAccountDeletionRequest']);
        // works with website and app authenticated
        Route::post('dynamic-account-number-here/adex/secure', [PaymentController::class, 'DynamicAccount']);
        Route::get('check/banks/user/gstar/{id}/secure/this/site/here', [Banks::class, 'GetBanksArray']);
        Route::post('update-kyc-here/adex/secure', [PaymentController::class, 'UpdateKYC']);
        Route::post('update-kyc-address/adex/secure', [PaymentController::class, 'UpdateKYCAddress']);
        Route::post('withdraw', [WithdrawalController::class, 'Transfer']);
    });
});