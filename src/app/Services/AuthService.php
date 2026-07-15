<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public static function panelMainAlpineJsComponent()
    {
        $user = Auth::user();

        if (!$user) return null;

        return match (true) {
            request()->is('panel*') => 'panel',
            request()->is('signer*') => 'signer',
            default => null,
        };
    }

    public static function getFormattedRole(User $user)
    {
        if (!$user) return null;

        return match ($user->role) {
            'root' => 'Root',
            'admin' => 'Administrador',
            'signer' => 'Signatário',
            default => null,
        };
    }
}
