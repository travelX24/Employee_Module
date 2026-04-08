@php
    $locale = app()->getLocale();
    $isRtl  = in_array(substr($locale, 0, 2), ['ar','fa','ur','he']);
    $dir    = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('topbar-left-content')
    <x-ui.page-header
        :title="tr('Employees')"
        :subtitle="tr('Search and filter employees')"
        class="!flex-col {{ $isRtl ? '!items-end !text-right' : '!items-start !text-left' }} !justify-start !gap-1"
        titleSize="xl"
    />
@endsection

<div class="space-y-4 sm:space-y-6 animate-pulse" dir="{{ $dir }}">

    {{-- Filters --}}
    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            @for($i=0; $i<6; $i++)
                <div class="space-y-2">
                    <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-10 bg-gray-100 rounded w-full"></div>
                </div>
            @endfor
        </div>
    </x-ui.card>

    {{-- Content --}}
    <x-ui.card>
        <div class="space-y-4">
            @for($i=0; $i<8; $i++)
                <div class="flex items-center space-x-4 space-x-reverse py-4 border-b border-gray-100">
                    <div class="w-12 h-12 bg-gray-200 rounded-xl shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                        <div class="h-3 bg-gray-100 rounded w-1/3"></div>
                    </div>
                    <div class="h-4 bg-gray-100 rounded w-20"></div>
                </div>
            @endfor
        </div>
    </x-ui.card>
</div>
