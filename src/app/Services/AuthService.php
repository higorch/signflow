<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public static function getFormattedRole(User $user)
    {
        if (!$user) return null;

        return match ($user->role) {
            'root' => 'Root',
            'admin' => 'Administrador',
            'customer' => 'Cliente',
            'signer' => 'Signatário',
            'staff' => 'Equipe',
            default => null,
        };
    }

    public static function sendResetPasswordLink(string $email): bool
    {
        $user = User::query()->where('email_hash', hmac_hash($email))->where('status', 'active')->first();

        if (!$user) return false;

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(['email_hash' => $user->email_hash], [
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $user->notify(new ResetPasswordNotification($token));

        return true;
    }

    public static function validateResetPasswordToken(string $email, string $token): bool
    {
        $user = User::query()->where('email_hash', hmac_hash($email))->where('status', 'active')->first();

        if (!$user) return false;

        $record = DB::table('password_reset_tokens')->where('email_hash', $user->email_hash)->first();

        if (!$record) return false;

        if (now()->diffInMinutes($record->created_at) > config('auth.passwords.users.expire')) {
            DB::table('password_reset_tokens')->where('email_hash', $user->email_hash)->delete();

            return false;
        }

        if (!Hash::check($token, $record->token)) return false;

        return true;
    }
}
