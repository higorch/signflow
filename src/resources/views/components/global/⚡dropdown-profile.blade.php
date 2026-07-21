<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view([
            'user' => $this->user,
            'profileUrl' => $this->profileUrl,
        ]);
    }

    #[On('refresh-user-avatar')]
    public function refreshUserAvatar()
    {
        $this->dispatch('$refresh');
    }

    #[On('logout')]
    public function logout()
    {
        Auth::logout(); // desloga o usuário

        request()->session()->invalidate(); // invalida a sessão
        request()->session()->regenerateToken(); // previne CSRF antigo

        return redirect()->route('auth.login');
    }

    #[Computed]
    public function user()
    {
        return Auth::user()->load([
            'avatar'
        ]);
    }

    #[Computed]
    public function profileUrl()
    {
        $user = $this->user;

        if (is_null($user)) return '#';

        return match ($user->role) {
            'root' => RouteService::absolute('panel.dashboard.index'),
            'signer' => RouteService::absolute('signer.profile.index'),
            default => route('panel.dashboard.index'),
        };
    }
};
?>

<div x-data="dropdown('bottom-end', 'absolute', 10)" @click.outside="open=false" class="relative z-20">
    <a x-ref="referenceDropdown" href="#" @click.prevent="open=!open" class="flex items-center gap-3">
        <span class="font-medium text-sm text-text-muted">
            {{ collect(explode(' ', trim($user->display_name)))->take(2)->implode(' ') }}
        </span>
        <div class="size-10 rounded-full overflow-hidden flex items-center justify-center text-[10px] font-semibold shrink-0 ring-2 bg-white/8 ring-white/20 text-text-soft">
            @if(optional($user)->avatar)
            <img src="{{ $user->avatar->public_url }}" class="w-full h-full object-cover">
            @else
            {{ initials($user->display_name) }}
            @endif
        </div>
    </a>
    <div x-ref="floatingDropdown" :class="{'flex':open,'hidden':!open}" class="absolute right-0 hidden w-40 flex-col gap-1 rounded-md border border-border bg-card p-2 shadow-lg">
        {{-- <a href="{{ $profileUrl }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
            <i class="las la-user text-base"></i> Meu perfil
        </a> --}}
        {{-- <div class="my-1 h-px bg-border"></div> --}}
        <a href="#" @click.prevent="$dispatch('logout')" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
            <i class="las la-sign-out-alt text-base"></i> Sair
        </a>
    </div>
</div>