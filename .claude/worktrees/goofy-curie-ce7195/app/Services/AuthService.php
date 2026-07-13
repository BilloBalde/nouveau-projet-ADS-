<?php

namespace App\Services;

use App\Models\User;
use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    public function generateOtp($phone)
    {
        OtpCode::where('phone', $phone)->where('verified', false)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
            'verified' => false,
        ]);

        $this->smsService->sendOtp($phone, $code);

        if (config('sms.show_code_flash')) {
            session()->flash('otp_demo', $code);
        }

        return $otp;
    }

    public function verifyOtp($phone, $code)
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('code', $code)
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otp) {
            $otp->update(['verified' => true]);
            return true;
        }

        return false;
    }

    public function register($data)
    {
        $digits = preg_replace('/\D/', '', $data['phone']);

        return User::create([
            'name' => $data['name'],
            'username' => 'c_'.$digits,
            'phone' => $data['phone'],
            'email' => 'c_'.$digits.'_'.uniqid('', true).'@clients.local',
            'password' => Hash::make(Str::random(16)),
            'token' => '',
            'role_id' => User::ROLE_CUSTOMER,
            'status' => 'Active',
        ]);
    }

    public function login($phone)
    {
        return User::where('phone', $phone)->first();
    }
}
