@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()"
        : '';

    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pageName = $paginator->getPageName();
    $rawPages = [];

    $addRange = function (int $from, int $to) use (&$rawPages, $lastPage): void {
        for ($page = max(1, $from); $page <= min($lastPage, $to); $page++) {
            $rawPages[$page] = $page;
        }
    };

    if ($lastPage <= 7) {
        $addRange(1, $lastPage);
    } else {
        $addRange(1, 3);
        $addRange($currentPage - 1, $currentPage + 1);
        $addRange($lastPage - 1, $lastPage);
    }

    ksort($rawPages);

    $pages = [];
    $previousPage = null;

    foreach (array_values($rawPages) as $page) {
        if ($previousPage !== null && $page > $previousPage + 1) {
            $pages[] = '...';
        }

        $pages[] = $page;
        $previousPage = $page;
    }
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700 leading-5">
                <span>Showing</span>
                <span class="font-medium">{{ $paginator->firstItem() }}</span>
                <span>to</span>
                <span class="font-medium">{{ $paginator->lastItem() }}</span>
                <span>of</span>
                <span class="font-medium">{{ $paginator->total() }}</span>
                <span>results</span>
            </p>
        </div>

        <div class="flex items-center overflow-x-auto">
            <span class="inline-flex rtl:flex-row-reverse rounded-md shadow-sm">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Previous">
                        <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-l-md leading-5" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-700 focus:z-10 focus:outline-none focus:border-[#903749] focus:ring ring-[#903749]/20 active:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif

                @foreach ($pages as $page)
                    @if ($page === '...')
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5">...</span>
                        </span>
                    @elseif ($page === $currentPage)
                        <span aria-current="page">
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-bold cursor-default leading-5 shadow-sm" style="background-color:#903749;border-color:#903749;color:#fff;">{{ $page }}</span>
                        </span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-900 hover:bg-gray-50 focus:z-10 focus:outline-none focus:border-[#903749] focus:ring ring-[#903749]/20 active:bg-gray-100 transition" aria-label="Go to page {{ $page }}">
                            {{ $page }}
                        </button>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-700 focus:z-10 focus:outline-none focus:border-[#903749] focus:ring ring-[#903749]/20 active:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @else
                    <span aria-disabled="true" aria-label="Next">
                        <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-r-md leading-5" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
