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
            <div class="stepper-scroll-wrap">
                @foreach($steps as $stepNum => $stepLabel)
                    @php
                        $isActive = ($tab == $stepNum);
                        $isCompleted = ($tab > $stepNum);
                        $isLast = $loop->last;
                    @endphp

                    <button
                        type="button"
                        wire:click="goToTab({{ $stepNum }})"
                        wire:loading.attr="disabled"
                        wire:target="goToTab"
                        class="stepper-btn group flex flex-col items-center gap-2 px-2 transition-all duration-200 flex-shrink-0 disabled:opacity-50 disabled:cursor-wait {{ $isActive ? 'scale-105' : 'hover:scale-105' }}"
                    >
                        <div class="relative w-10 h-10 transition-all duration-200">
                            <svg viewBox="0 0 56 56" class="absolute inset-0 drop-shadow-sm">
                                <polygon
                                    points="28,4 48,20 40,48 16,48 8,20"
                                    fill="{{ $isActive ? 'var(--brand-via)' : ($isCompleted ? 'var(--brand-via)' : '#f3f4f6') }}"
                                    stroke="{{ $isActive ? 'var(--brand-via)' : ($isCompleted ? 'var(--brand-via)' : '#d1d5db') }}"
                                    stroke-width="2.5"
                                    class="transition-all duration-200"
                                />
                            </svg>
                            <div
                                class="absolute inset-0 flex items-center justify-center text-sm font-extrabold transition-colors duration-200 {{ $isActive || $isCompleted ? 'text-white' : 'text-gray-600' }}"
                            >
                                <span>{{ $isCompleted ? '✓' : $stepNum }}</span>
                            </div>
                        </div>
                        <div
                            class="text-[10px] font-semibold text-center leading-tight transition-colors duration-200 max-w-[70px] {{ $isActive || $isCompleted ? 'text-[color:var(--brand-via)]' : 'text-gray-500' }}"
                        >
                            {{ $stepLabel }}
                        </div>
                    </button>

                    @if(!$isLast)
                        <div
                            class="h-[3px] w-6 rounded-full transition-all duration-300 flex-shrink-0 self-start mt-5 {{ $isCompleted ? 'bg-[color:var(--brand-via)]' : 'bg-gray-200' }}"
                        ></div>
                    @endif
                @endforeach
            </div>

            <div class="text-center mt-4">
                <span class="text-sm font-bold text-[color:var(--brand-via)] bg-white/60 px-4 py-1.5 rounded-full inline-block shadow-sm">
                    {{ tr('Step') }} {{ $tab }} {{ tr('of') }} 5: {{ $steps[$tab] ?? '' }}
                </span>
            </div>

            <style>
                .stepper-scroll-wrap {
                    width: 100%;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                    padding: 4px 8px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                .stepper-scroll-wrap::-webkit-scrollbar { display: none; }

                /* Desktop: center */
                @media (min-width: 541px) {
                    .stepper-scroll-wrap {
                        justify-content: center;
                    }
                }
            </style>
        </div>

        {{-- Content --}}
        <div class="p-3 sm:p-4 md:p-6">
            @if($tab === 1)
                <div wire:key="tab-1-content">@include('employees::livewire.employees.partials.tab-basic')</div>
            @elseif($tab === 2)
                <div wire:key="tab-2-content">@include('employees::livewire.employees.partials.tab-job')</div>
            @elseif($tab === 3)
                <div wire:key="tab-3-content">@include('employees::livewire.employees.partials.tab-financial')</div>
            @elseif($tab === 4)
                <div wire:key="tab-4-content">@include('employees::livewire.employees.partials.tab-personal')</div>
            @elseif($tab === 5)
                <div wire:key="tab-5-content">@include('employees::livewire.employees.partials.tab-documents')</div>
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




