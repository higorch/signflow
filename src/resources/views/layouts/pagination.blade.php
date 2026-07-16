@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

@if ($paginator->hasPages())
    <div class="flex flex-col md:flex-row justify-between items-center gap-3">

        <span class="mb-2 md:mb-0 text-sm text-[#fff0d7]/50">
            {!! __('pagination.Showing') !!}
            {{ $paginator->firstItem() }}
            {!! __('pagination.to') !!}
            {{ $paginator->lastItem() }}
            {!! __('pagination.of') !!}
            {{ $paginator->total() }}
            {!! __('pagination.results') !!}
        </span>
    
        <div class="flex items-center">
    
            @if ($paginator->onFirstPage())
                <span class="mr-3 text-xs cursor-not-allowed text-[#434050]"><i class="las la-angle-left"></i></span>
            @else
                <a href="#" wire:click.prevent="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="mr-3 text-xs text-[#CBD5E1] hover:text-white transition cursor-pointer"><i class="las la-angle-left"></i></a>
            @endif

            <div class="flex items-stretch gap-1">
            @foreach ($elements as $element)

                @if (is_string($element))
                    <span class="px-2 py-1 text-sm rounded-sm text-[#CBD5E1] cursor-no-drop">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-2 py-1 text-sm cursor-no-drop rounded-sm border border-white/10 bg-white/10 text-[#fff0d7] font-semibold">{{ $page }}</span>
                        @else
                            <a href="#" wire:click.prevent="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="gotoPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.{{ $page }}" class="px-2 py-1 text-sm rounded-sm text-[#434050] hover:text-[#fff0d7] hover:bg-white/10 transition cursor-pointer">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif

            @endforeach
            </div>
    
            @if ($paginator->hasMorePages())
                <a href="#" wire:click.prevent="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="ml-3 text-xs text-[#CBD5E1] hover:text-white transition cursor-pointer"><i class="las la-angle-right"></i></a>               
            @else
                <span class="ml-3 text-xs cursor-not-allowed text-[#434050]"><i class="las la-angle-right"></i></span>
            @endif
    
        </div>
    
    </div>
@endif