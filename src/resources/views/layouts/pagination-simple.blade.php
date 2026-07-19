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
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center justify-between gap-3 sm:flex-row">

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
            <span class="inline-flex h-7 items-center gap-1 rounded border border-border bg-input px-2.5 text-xs font-medium text-placeholder cursor-not-allowed">
                <i class="las la-angle-left"></i>
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="#" wire:click.prevent="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="inline-flex h-7 items-center gap-1 rounded border border-border bg-input px-2.5 text-xs font-medium text-text-muted transition hover:border-border-hover hover:bg-input-focus hover:text-text">
                <i class="las la-angle-left"></i>
                {!! __('pagination.previous') !!}
            </a>
        @endif

        <span class="inline-flex h-7 items-center rounded border border-primary bg-primary px-2.5 text-xs font-semibold text-white">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a href="#" wire:click.prevent="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="inline-flex h-7 items-center gap-1 rounded border border-border bg-input px-2.5 text-xs font-medium text-text-muted transition hover:border-border-hover hover:bg-input-focus hover:text-text">
                {!! __('pagination.next') !!}
                <i class="las la-angle-right"></i>
            </a>
        @else
            <span class="inline-flex h-7 items-center gap-1 rounded border border-border bg-input px-2.5 text-xs font-medium text-placeholder cursor-not-allowed">
                {!! __('pagination.next') !!}
                <i class="las la-angle-right"></i>
            </span>
        @endif

    </div>

</nav>
@endif