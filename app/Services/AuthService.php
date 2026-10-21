<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Numéro mobile guinéen : neuf chiffres commençant par 6, avec un indicatif
     * pays facultatif. C'est le seul format que la boutique dessert, autant le
     * dire au client au moment de la saisie plutôt qu'après coup.
     */
    public const REGLE_TELEPHONE = 'regex:/^(?:\+|00)?224[\s.-]?6\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}$|^6\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}$/';

    public const MESSAGE_TELEPHONE = 'Entrez un numéro guinéen valide : 9 chiffres commençant par 6 (exemple : 622 33 44 55).';

    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Ramène un numéro à ses neuf chiffres locaux, indicatif et séparateurs ôtés.
     *
     * Sans cela, « +224 622 33 44 55 » et « 622334455 » créeraient deux comptes
     * distincts, et un client inscrit sous une forme ne se reconnecterait pas
     * sous l'autre. Le format court est celui déjà en base.
     */
    public static function normaliserTelephone(string $phone): string
    {
        $chiffres = preg_replace('/\D/', '', $phone);

        if (str_starts_with($chiffres, '00224')) {
            $chiffres = substr($chiffres, 5);
        } elseif (str_starts_with($chiffres, '224') && strlen($chiffres) > 9) {
            $chiffres = substr($chiffres, 3);
        }

        return $chiffres;
    }

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
        $phone = self::normaliserTelephone($data['phone']);

        return User::create([
            'name' => $data['name'],
            'username' => 'c_'.$phone,
            'phone' => $phone,
            'email' => 'c_'.$phone.'_'.uniqid('', true).'@clients.local',
            'password' => Hash::make(Str::random(16)),
            'token' => '',
            'role_id' => User::ROLE_CUSTOMER,
            'status' => 'Active',
        ]);
    }

    public function login($phone)
    {
        return User::where('phone', self::normaliserTelephone($phone))->first();
    }
}
