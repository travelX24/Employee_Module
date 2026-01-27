@php
    $locale = app()->getLocale();
    $isRtl  = in_array(substr($locale, 0, 2), ['ar','fa','ur','he']);
    $dir    = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('topbar-left-content')
    <x-ui.page-header
        :title="tr('Add Employee')"
        :subtitle="tr('Create a new employee profile')"
        class="!flex-col {{ $isRtl ? '!items-end !text-right' : '!items-start !text-left' }} !justify-start !gap-1"
        titleSize="xl"
    />
@endsection
@section('topbar-actions')
    <x-ui.secondary-button
        href="{{ route('company-admin.employees.index') }}"
        :arrow="false"
        :fullWidth="false"
        class="!px-4 !py-2 !text-sm !rounded-xl !gap-2"
    >
        <i class="fas {{ $isRtl ? 'fa-arrow-right' : 'fa-arrow-left' }} text-xs"></i>
        <span>{{ tr('Back') }}</span>
    </x-ui.secondary-button>
@endsection




    @php
        $steps = [
            1 => tr('Basic Information'),
            2 => tr('Job Information'),
            3 => tr('Financial Information'),
            4 => tr('Personal Information'),
            5 => tr('Documents'),
        ];
    @endphp
<div class="space-y-4">
    {{-- ✅ Single Card --}}
    <div class="bg-white rounded-2xl shadow border border-gray-100 min-h-[500px]">
        {{-- ... existing content inside the card ... --}}
        {{-- I will use the actual content here instead of placeholders --}}
        {{-- confirming content from line 43 to 235 --}}

        {{-- Confirmation Dialog --}}
        <x-ui.confirm-dialog
            id="save-employee"
            :title="tr('Confirm Save Employee')"
            :message="tr('Are you sure you want to save this employee? Please review all information before proceeding.')"
            :confirmText="tr('Yes, Save Employee')"
            :cancelText="tr('Cancel')"
            confirmAction="wire:store"
            type="info"
            icon="fa-user"
        />

        {{-- Stepper --}}
        <div class="px-3 sm:px-4 md:px-6 py-4 sm:py-5 bg-gray-50/40">
            <style>
                .stepper-container {
                    overflow-x: hidden !important;
                    overflow-y: hidden !important;
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                }
                .stepper-container::-webkit-scrollbar {
                    display: none;
                }
            </style>

            {{-- Desktop / Tablet --}}
            <div class="hidden sm:block stepper-container">
                <div class="flex justify-center">
                    <div class="flex items-center justify-center flex-wrap gap-2">
                        @foreach($steps as $stepNum => $stepLabel)
                            @php
                                $isActive = ($tab == $stepNum);
                                $isCompleted = ($tab > $stepNum);
                                $isLast = $loop->last;
                            @endphp

                            <button type="button"
                                    wire:click="goToTab({{ $stepNum }})"
                                    wire:loading.attr="disabled"
                                    wire:target="goToTab"
                                    class="group flex flex-col items-center gap-1 px-1 disabled:opacity-50 disabled:cursor-wait">
                                <div class="relative w-10 sm:w-12 h-10 sm:h-12 transition-transform duration-200 group-hover:scale-105">
                                    <svg viewBox="0 0 56 56" class="absolute inset-0">
                                        <polygon points="28,4 48,20 40,48 16,48 8,20"
                                                 fill="{{ $isActive ? 'var(--brand-via)' : ($isCompleted ? 'var(--brand-via)' : '#f3f4f6') }}"
                                                 stroke="{{ $isActive ? 'var(--brand-via)' : ($isCompleted ? 'var(--brand-via)' : '#d1d5db') }}"
                                                 stroke-width="2"/>
                                    </svg>

                                    <div class="absolute inset-0 flex items-center justify-center text-sm sm:text-base font-extrabold
                                        {{ $isActive || $isCompleted ? 'text-white' : 'text-gray-700' }}">
                                        {{ $isCompleted ? '✓' : $stepNum }}
                                    </div>
                                </div>

                                <div class="text-[10px] sm:text-[11px] font-semibold text-center leading-4 max-w-[100px] sm:max-w-[120px]
                                    {{ $isActive || $isCompleted ? 'text-[color:var(--brand-via)]' : 'text-gray-500' }}">
                                    {{ $stepLabel }}
                                </div>
                            </button>

                            @if(! $isLast)
                                <div class="h-[3px] w-8 sm:w-12 md:w-16 lg:w-20 mt-6 rounded-full
                                    {{ $tab > $stepNum ? 'bg-[color:var(--brand-via)]' : 'bg-gray-200' }}">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="text-center mt-3">
                    <span class="text-xs sm:text-sm font-semibold text-[color:var(--brand-via)]">
                        {{ tr('Step') }} {{ $tab }} {{ tr('of') }} 5: {{ $steps[$tab] ?? '' }}
                    </span>
                </div>
            </div>

            {{-- Mobile --}}
            <div class="sm:hidden">
                <div class="flex justify-center">
                    <div class="inline-flex items-center gap-2">
                        @foreach($steps as $stepNum => $stepLabel)
                            @php
                                $isActive = ($tab == $stepNum);
                                $isCompleted = ($tab > $stepNum);
                                $isLast = $loop->last;
                            @endphp

                            <button type="button" wire:click="goToTab({{ $stepNum }})" wire:loading.attr="disabled" wire:target="goToTab" class="group disabled:opacity-50 disabled:cursor-wait">
                                <div class="relative w-10 h-10">
                                    <svg viewBox="0 0 56 56" class="absolute inset-0">
                                        <polygon points="28,4 48,20 40,48 16,48 8,20"
                                                 fill="{{ $isActive ? 'var(--brand-via)' : ($isCompleted ? 'var(--brand-via)' : '#f3f4f6') }}"
                                                 stroke="{{ $isActive ? 'var(--brand-via)' : ($isCompleted ? 'var(--brand-via)' : '#d1d5db') }}"
                                                 stroke-width="2"/>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center text-sm font-extrabold
                                        {{ $isActive || $isCompleted ? 'text-white' : 'text-gray-700' }}">
                                        {{ $isCompleted ? '✓' : $stepNum }}
                                    </div>
                                </div>
                            </button>

                            @if(! $isLast)
                                <div class="h-[3px] w-7 rounded-full
                                    {{ $tab > $stepNum ? 'bg-[color:var(--brand-via)]' : 'bg-gray-200' }}">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="text-center mt-3 text-sm font-semibold text-[color:var(--brand-via)]">
                    {{ tr('Step') }} {{ $tab }} / 5 — {{ $steps[$tab] ?? '' }}
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="p-3 sm:p-4 md:p-6">
            @if($tab === 1)
                @include('employees::livewire.employees.partials.tab-basic')
            @elseif($tab === 2)
                @include('employees::livewire.employees.partials.tab-job')
            @elseif($tab === 3)
                @include('employees::livewire.employees.partials.tab-financial')
            @elseif($tab === 4)
                @include('employees::livewire.employees.partials.tab-personal')
            @elseif($tab === 5)
                @include('employees::livewire.employees.partials.tab-documents')
            @endif
        </div>

        {{-- Actions --}}
        <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-t border-gray-100 bg-white">
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 sm:items-center sm:justify-between">
                <div class="w-full sm:w-auto sm:inline-flex">
                    <x-ui.secondary-button
                        type="button"
                        wire:click="prevTab"
                        :disabled="$tab === 1"
                        :fullWidth="true"
                        :arrow="true"
                        arrowDirection="left"
                        class="{{ $tab === 1 ? 'opacity-40 cursor-not-allowed' : '' }}"
                    >
                        {{ tr('Previous') }}
                    </x-ui.secondary-button>
                </div>

                <div class="w-full sm:w-auto sm:inline-flex">
                    @if($tab < 5)
                        <x-ui.primary-button
                            type="button"
                            wire:click="nextTab"
                            :fullWidth="true"
                            wire:loading.attr="disabled"
                            wire:target="nextTab"
                        >
                            <span wire:loading.remove wire:target="nextTab">
                                {{ tr('Next') }}
                            </span>
                            <span wire:loading wire:target="nextTab" class="flex items-center gap-2">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>{{ tr('Validating...') }}</span>
                            </span>
                        </x-ui.primary-button>
                    @else
                        <div wire:loading.class="opacity-50 pointer-events-none" wire:target="store">
                            <x-ui.primary-button
                                type="button"
                                :arrow="false"
                                :fullWidth="true"
                                wire:loading.attr="disabled"
                                wire:target="store"
                                x-on:click="$dispatch('open-confirm-save-employee')"
                                class="disabled:opacity-50 disabled:cursor-wait"
                            >
                                <span wire:loading.remove wire:target="store" class="flex items-center gap-2">
                                    <i class="fas fa-save"></i>
                                    <span>{{ tr('Save Employee') }}</span>
                                </span>
                                <span wire:loading wire:target="store" class="flex items-center gap-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>{{ tr('Saving...') }}</span>
                                </span>
                            </x-ui.primary-button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>




