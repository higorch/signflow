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

    <span class="mb-2 md:mb-0 text-sm text-[#fff0d7]/50">
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
            <span class="inline-flex items-center rounded-md border border-[#2d1218] bg-[#211015]/80 px-4 py-2 text-sm font-medium text-[#5f4d47] cursor-not-allowed">
                <i class="las la-angle-left mr-1"></i>
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="#" wire:click.prevent="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="inline-flex items-center rounded-md border border-[#4d2b32] bg-[#2b1218]/80 px-4 py-2 text-sm font-medium text-[#efc587] transition-all duration-200 hover:border-[#efc587]/40 hover:bg-[#34151d]">
                <i class="las la-angle-left mr-1"></i>
                {!! __('pagination.previous') !!}
            </a>
        @endif

        <div class="rounded-md border border-[#4d2b32] bg-[#2b1218]/80 px-3 py-2 text-sm font-semibold text-[#efc587]">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </div>

        @if ($paginator->hasMorePages())
            <a href="#" wire:click.prevent="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="inline-flex items-center rounded-md border border-[#4d2b32] bg-[#2b1218]/80 px-4 py-2 text-sm font-medium text-[#efc587] transition-all duration-200 hover:border-[#efc587]/40 hover:bg-[#34151d]">
                {!! __('pagination.next') !!}
                <i class="las la-angle-right ml-1"></i>
            </a>
        @else
            <span class="inline-flex items-center rounded-md border border-[#2d1218] bg-[#211015]/80 px-4 py-2 text-sm font-medium text-[#5f4d47] cursor-not-allowed">
                {!! __('pagination.next') !!}
                <i class="las la-angle-right ml-1"></i>
            </span>
        @endif

    </div>

</nav>
@endif