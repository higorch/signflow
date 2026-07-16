<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
            default => null,
        };
    }
}
