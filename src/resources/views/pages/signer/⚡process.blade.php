<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
       return $this->view([
            'pageTitle' => $this->pageTitle
        ])->layout('layouts::signer')->title($this->pageTitle);
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Assinar Processo';
    }
};
?>

<div>
    {{-- Be present above all else. - Naval Ravikant --}}
</div>