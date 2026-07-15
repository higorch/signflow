<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[On('refresh-user-avatar')]
    public function refreshUserAvatar()
    {
        $this->dispatch('$refresh');
    }

    #[On('logout')]
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('auth.login');
    }
};
?>

<div class="px-6 lg:px-10 py-5 mb-5 border-b border-[#fada82]/5">

    <div class="flex items-center justify-between gap-2">

        <div class="flex items-start gap-4">
            <button @click="mobileMenu=true" class="lg:hidden relative cursor-pointer w-10 h-10 flex items-center justify-center rounded-md bg-white/8 hover:bg-white/10 active:scale-95 transition shadow-md ring-1 ring-white/10">
                <div class="flex flex-col text-[#e3e3e3]">
                    <i class="las la-bars text-xl"></i>
                    <span class="text-[8px]">Menu</span>
                </div>
                <i class="las la-angle-right text-[10px] absolute -left-1 -bottom-1 rounded-full p-0.5 bg-[#1d49bd] text-[#e3e3e3]"></i>
            </button>
            <button @click="menuOpen=!menuOpen" class="hidden lg:flex relative cursor-pointer w-10 h-10 items-center justify-center rounded-md bg-white/8 hover:bg-white/10 active:scale-95 transition shadow-md ring-1 ring-white/10">
                <div class="flex flex-col text-[#e3e3e3]">
                    <i class="las la-bars text-xl"></i>
                    <span class="text-[8px]">Menu</span>
                </div>
                <i class="las text-[10px] absolute -left-1.5 -bottom-1.5 rounded-full p-0.5 bg-[#1d49bd] text-[#e3e3e3] transition-all duration-300" :class="menuOpen ? 'la-angle-left' : 'la-angle-right'"></i>
            </button>
        </div>

        <livewire:global.dropdown-profile />

    </div>

</div>