<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class VerifyEmailProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Confirme seu e-mail - Sign Flow')
                ->greeting('Confirme seu e-mail')
                ->line('Estamos quase lá! Clique no botão abaixo para ativar sua conta.')
                ->action('Confirmar e-mail', $url)
                ->line('Se você não criou uma conta, ignore este e-mail.')
                ->salutation('— Equipe Sign Flow 📄🚀');
        });
    }
}
