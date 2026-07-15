<?php

namespace App\View\Components\Global;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LimitInput extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $model,
        public int $limit,
        public bool $stop = false,
        public string $align = 'bottom' // top, bottom, center
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.global.limit-input', [
            'model' => $this->model,
            'limit' => $this->limit,
            'stop' => $this->stop,
            'align' => $this->align,
        ]);
    }
}