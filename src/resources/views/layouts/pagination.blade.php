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
    <div class="flex flex-col items-center justify-between gap-3 md:flex-row">

        <span class="text-sm text-text-muted">
            {!! __('pagination.Showing') !!}
            {{ $paginator->firstItem() }}
            {!! __('pagination.to') !!}
            {{ $paginator->lastItem() }}
            {!! __('pagination.of') !!}
            {{ $paginator->total() }}
            {!! __('pagination.results') !!}
        </span>

        <div class="flex items-center gap-2">

            @if ($paginator->onFirstPage())
                <span class="flex size-7 items-center justify-center rounded border border-border bg-input text-placeholder cursor-not-allowed">
                    <i class="las la-angle-left"></i>
                </span>
            @else
                <a href="#" wire:click.prevent="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="flex size-7 items-center justify-center rounded border border-border bg-input text-text-muted transition hover:border-border-hover hover:bg-input-focus hover:text-text">
                    <i class="las la-angle-left"></i>
                </a>
            @endif

            <div class="flex items-center gap-1">
                @foreach ($elements as $element)

                    @if (is_string($element))
                        <span class="px-1.5 text-xs text-placeholder">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="flex h-7 min-w-7 items-center justify-center rounded border border-primary bg-primary px-2 text-xs font-semibold text-white">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="#" wire:click.prevent="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="gotoPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.{{ $page }}" class="flex h-7 min-w-7 items-center justify-center rounded border border-border bg-input px-2 text-xs text-text-muted transition hover:border-border-hover hover:bg-input-focus hover:text-text">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif

                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="#" wire:click.prevent="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="flex size-7 items-center justify-center rounded border border-border bg-input text-text-muted transition hover:border-border-hover hover:bg-input-focus hover:text-text">
                    <i class="las la-angle-right"></i>
                </a>
            @else
                <span class="flex size-7 items-center justify-center rounded border border-border bg-input text-placeholder cursor-not-allowed">
                    <i class="las la-angle-right"></i>
                </span>
            @endif

        </div>

    </div>
@endif