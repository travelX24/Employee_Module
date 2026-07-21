<div x-data="{ tab: @entangle('tab') }" class="employee-edit-compact">
    @php
        $lastEditableTab = auth()->user()->can('employees.documents.manage') ? 5 : 4;
    @endphp
    {{-- Stepper Navigation --}}
    <div class="mb-3">
        <div class="stepper-scroll-wrap">
            @foreach([
                1 => tr('Basic Information'),
                2 => tr('Job Information'),
                3 => tr('Financial Information'),
                4 => tr('Personal Information'),
                5 => tr('Documents'),
            ] as $stepNum => $stepLabel)
                @continue($stepNum === 3 && !auth()->user()->can('employees.contracts.manage'))
                @continue($stepNum === 5 && !auth()->user()->can('employees.documents.manage'))
                <button
                    type="button"
                    wire:click="goToTab({{ $stepNum }})"
                    wire:loading.attr="disabled"
                    wire:target="goToTab,nextTab,previousTab"
                    data-step="{{ $stepNum }}"
                    class="stepper-btn group flex flex-col items-center gap-1 px-1.5 transition-all duration-200 flex-shrink-0"
                    :class="tab === {{ $stepNum }} ? 'scale-105' : 'hover:scale-105'"
                >
                    <div class="relative w-9 h-9 transition-all duration-200">
                        <svg viewBox="0 0 56 56" class="absolute inset-0 drop-shadow-sm">
                            <polygon
                                points="28,4 48,20 40,48 16,48 8,20"
                                :fill="tab === {{ $stepNum }} ? 'var(--accent-orange)' : (tab > {{ $stepNum }} ? 'var(--accent-orange)' : '#f3f4f6')"
                                :stroke="tab === {{ $stepNum }} ? 'var(--accent-orange)' : (tab > {{ $stepNum }} ? 'var(--accent-orange)' : '#d1d5db')"
                                stroke-width="2.5"
                                class="transition-all duration-200"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex items-center justify-center text-sm font-extrabold transition-colors duration-200"
                            :class="tab === {{ $stepNum }} || tab > {{ $stepNum }} ? 'text-white' : 'text-gray-600'"
                        >
                            <span x-show="tab > {{ $stepNum }}">✓</span>
                            <span x-show="tab <= {{ $stepNum }}">{{ $stepNum }}</span>
                        </div>
                    </div>
                    <div
                        class="text-[10px] font-semibold text-center leading-tight transition-colors duration-200 max-w-[70px]"
                        :class="tab === {{ $stepNum }} || tab > {{ $stepNum }} ? 'text-[color:var(--accent-orange)]' : 'text-gray-500'"
                    >
                        {{ $stepLabel }}
                    </div>
                </button>

                @if(!$loop->last)
                    <div
                        class="h-[2px] w-5 rounded-full transition-all duration-300 flex-shrink-0 self-start mt-4"
                        :class="tab > {{ $stepNum }} ? 'bg-[color:var(--accent-orange)]' : 'bg-gray-200'"
                    ></div>
                @endif
            @endforeach
        </div>
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

        .employee-edit-compact .edit-tab-panel > [class*="space-y-"] > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0.875rem !important;
        }

        .employee-edit-compact .edit-tab-panel [class~="gap-4"],
        .employee-edit-compact .edit-tab-panel [class~="gap-5"],
        .employee-edit-compact .edit-tab-panel [class~="gap-6"],
        .employee-edit-compact .edit-tab-panel [class~="md:gap-4"],
        .employee-edit-compact .edit-tab-panel [class~="md:gap-5"],
        .employee-edit-compact .edit-tab-panel [class~="md:gap-6"],
        .employee-edit-compact .edit-tab-panel [class~="lg:gap-4"],
        .employee-edit-compact .edit-tab-panel [class~="lg:gap-5"],
        .employee-edit-compact .edit-tab-panel [class~="lg:gap-6"] {
            gap: 0.875rem !important;
        }

        .employee-edit-compact .edit-tab-panel [class~="p-5"] {
            padding: 1rem !important;
        }

        .employee-edit-compact .edit-tab-panel [class~="p-4"] {
            padding: 0.875rem !important;
        }

        .employee-edit-compact .edit-tab-panel label {
            margin-bottom: 0.25rem !important;
        }

        .employee-edit-compact .edit-tab-panel input:not([type="file"]):not([type="checkbox"]):not([type="radio"]),
        .employee-edit-compact .edit-tab-panel textarea {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        /* Desktop: center */
        @media (min-width: 541px) {
            .stepper-scroll-wrap {
                justify-content: center;
            }
        }
    </style>


    <form wire:submit.prevent="save" novalidate>
        <div class="space-y-4">
            {{-- Tab 1: Basic Information --}}
            <div x-show="tab === 1" x-transition class="edit-tab-panel">
                @include('employees::livewire.employees.partials.tab-basic')
            </div>

            {{-- Tab 2: Job Information --}}
            <div x-show="tab === 2" x-transition class="edit-tab-panel">
                @include('employees::livewire.employees.partials.tab-job')
            </div>

            {{-- Tab 3: Financial Information --}}
            @can('employees.contracts.manage')
            <div x-show="tab === 3" x-transition class="edit-tab-panel">
                @include('employees::livewire.employees.partials.tab-financial')
            </div>
            @endcan

            {{-- Tab 4: Personal Information --}}
            <div x-show="tab === 4" x-transition class="edit-tab-panel">
                @include('employees::livewire.employees.partials.tab-personal')
            </div>

            {{-- Tab 5: Documents --}}
            @can('employees.documents.manage')
            <div x-show="tab === {{ $lastEditableTab }}" x-transition class="edit-tab-panel">
                @include('employees::livewire.employees.partials.tab-documents-edit')
            </div>
            @endcan



            {{-- Navigation Buttons --}}
            <div class="flex justify-between gap-2 pt-3 border-t border-gray-200">
                <button
                    type="button"
                    wire:click="previousTab"
                    x-show="tab > 1"
                    class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 hover:border-gray-400 hover:shadow-md active:scale-[0.97] transition-all duration-200 shadow-sm"
                >
                    <i class="fas fa-arrow-right me-2"></i>
                    {{ tr('Previous') }}
                </button>

                <div class="flex-1"></div>

                <button
                    type="button"
                    wire:click="nextTab"
                    x-show="tab < {{ $lastEditableTab }}"
                    class="px-5 py-2.5 text-sm font-semibold text-white bg-[color:var(--accent-orange)] rounded-xl hover:shadow-lg hover:brightness-95 active:scale-[0.97] transition-all duration-200 shadow-sm"
                >
                    {{ tr('Next') }}
                    <i class="fas fa-arrow-left ms-2"></i>
                </button>

                <button
                    type="submit"
                    x-show="tab === {{ $lastEditableTab }}"
                    wire:loading.attr="disabled"
                    class="px-5 py-2.5 text-sm font-semibold text-white bg-[color:var(--accent-orange)] rounded-xl hover:shadow-lg hover:brightness-95 active:scale-[0.97] transition-all duration-200 shadow-sm disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="save">
                        <i class="fas fa-save me-2"></i>
                        {{ tr('Save Changes') }}
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        {{ tr('Saving...') }}
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>




