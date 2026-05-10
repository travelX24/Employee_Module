<div>
    @if($employee)
    
@php
    $steps = [
        1 => tr('Basic Information'),
        2 => tr('Job Information'),
        3 => tr('Financial Information'),
        4 => tr('Personal Information'),
        5 => tr('Documents'),
        6 => tr('Status History'),
    ];
    $locale = app()->getLocale();
@endphp

    <div
        wire:key="employee-detail-wrap-{{ $employee->id }}"
        x-data="{
            activeTab: 1,
            editMode: false,
            employeeId: {{ $employee->id }},
            isModalOpen: @entangle('show'),

            init() {
                this.$watch('isModalOpen', (value) => {
                    if (value === true) {
                        this.editMode = false;
                        this.activeTab = 1;
                    }
                });
            },

            show() {
                this.editMode = false;
                this.activeTab = 1;
            },

            hide() {
                this.editMode = false;
                this.isModalOpen = false;
            },

            enableEdit() {
                this.editMode = true;
                this.activeTab = 1;
            },

            cancelEdit() {
                this.editMode = false;
                window.dispatchEvent(new CustomEvent('employee-edit-cancelled'));
            },

            reopenAfterSave() {
                this.editMode = false;
                this.activeTab = 1;
            }
        }"
        x-on:open-view-employee-{{ $employee->id }}.window="show()"
        x-on:employee-updated.window="
            const updatedId = $event.detail?.employeeId ?? $event.detail?.[0]?.employeeId ?? $event.detail?.[0] ?? null;
            if (updatedId != employeeId) return;
            editMode = false;
            reopenAfterSave();
        "
    >
        <x-ui.modal wire:model="show" maxWidth="5xl">
            <x-slot name="title">
<div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
                <h3 class="text-xl font-bold text-gray-900">
                    <span x-show="!editMode">{{ tr('Employee Details') }}</span>
                    <span x-show="editMode">{{ tr('Edit Employee') }}</span>
                </h3>

                <p class="text-xs text-gray-600 mt-1 mb-0 leading-normal font-normal">
                    @if($locale === 'ar')
                        {{ $employee->name_ar }}
                    @else
                        {{ $employee->name_en ?: $employee->name_ar }}
                    @endif
                    <span class="mx-2 text-gray-300">|</span>
                    <span class="text-gray-400 font-mono">{{ $employee->employee_no }}</span>
                </p>
            </div>
        </div>
            </x-slot>

            <x-slot name="icon">
<i class="fas fa-user-tie text-white text-xl"></i>
            </x-slot>

            <div class="flex flex-col min-h-0">
                {{-- Stepper (View mode only) --}}
        <div
            class="px-6 py-5 bg-gradient-to-br from-gray-50 via-gray-50/80 to-gray-50/60 border-b border-gray-200/50"
            x-show="!editMode"
        >
        {{-- Unified Responsive Stepper (View Mode) --}}
        <div class="stepper-scroll-wrap">
            @foreach($steps as $stepNum => $stepLabel)
                <button
                    type="button"
                    @click="activeTab = {{ $stepNum }}"
                    data-step="{{ $stepNum }}"
                    class="stepper-btn group flex flex-col items-center gap-2 px-2 transition-all duration-200 flex-shrink-0"
                    :class="activeTab === {{ $stepNum }} ? 'scale-105' : 'hover:scale-105'"
                >
                    <div class="relative w-10 h-10 transition-all duration-200">
                        <svg viewBox="0 0 56 56" class="absolute inset-0 drop-shadow-sm">
                            <polygon
                                points="28,4 48,20 40,48 16,48 8,20"
                                :fill="activeTab === {{ $stepNum }} ? 'var(--brand-via)' : (activeTab > {{ $stepNum }} ? 'var(--brand-via)' : '#f3f4f6')"
                                :stroke="activeTab === {{ $stepNum }} ? 'var(--brand-via)' : (activeTab > {{ $stepNum }} ? 'var(--brand-via)' : '#d1d5db')"
                                stroke-width="2.5"
                                class="transition-all duration-200"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex items-center justify-center text-sm font-extrabold transition-colors duration-200"
                            :class="activeTab === {{ $stepNum }} || activeTab > {{ $stepNum }} ? 'text-white' : 'text-gray-600'"
                        >
                            <span x-show="activeTab > {{ $stepNum }}">✓</span>
                            <span x-show="activeTab <= {{ $stepNum }}">{{ $stepNum }}</span>
                        </div>
                    </div>
                    <div
                        class="text-[10px] font-semibold text-center leading-tight transition-colors duration-200 max-w-[70px]"
                        :class="activeTab === {{ $stepNum }} || activeTab > {{ $stepNum }} ? 'text-[color:var(--brand-via)]' : 'text-gray-500'"
                    >
                        {{ $stepLabel }}
                    </div>
                </button>

                @if(!$loop->last)
                    <div
                        class="h-[3px] w-6 rounded-full transition-all duration-300 flex-shrink-0 self-start mt-5"
                        :class="activeTab > {{ $stepNum }} ? 'bg-[color:var(--brand-via)]' : 'bg-gray-200'"
                    ></div>
                @endif
            @endforeach
        </div>

        <div class="text-center mt-4">
            <span class="text-sm font-bold text-[color:var(--brand-via)] bg-white/60 px-4 py-1.5 rounded-full inline-block shadow-sm">
                {{ tr('Step') }} <span x-text="activeTab"></span> {{ tr('of') }} 6:
                <span x-text="['{{ $steps[1] }}', '{{ $steps[2] }}', '{{ $steps[3] }}', '{{ $steps[4] }}', '{{ $steps[5] }}', '{{ $steps[6] }}'][activeTab - 1]"></span>
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
        <div class="p-6 pb-32">
            {{-- View Mode --}}
            <div x-show="!editMode" x-transition>
                <div x-show="activeTab === 1" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-basic', ['employee' => $employee])
                </div>

                <div x-show="activeTab === 2" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-job', ['employee' => $employee])
                </div>

                <div x-show="activeTab === 3" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-financial', ['employee' => $employee])
                </div>

                <div x-show="activeTab === 4" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-personal', ['employee' => $employee])
                </div>

                <div x-show="activeTab === 5" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-documents', ['employee' => $employee])
                </div>

                <div x-show="activeTab === 6" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-history', ['employee' => $employee])
                </div>
            </div>

            {{-- Edit Mode --}}
            <div x-show="editMode" x-transition class="w-full">
                @livewire('employees.edit', ['employeeId' => $employee->id], key('edit-employee-'.$employee->id))
            </div>
        </div>
        <div class="sticky bottom-0 px-6 py-4 border-t border-gray-200 bg-white shadow-[0_-8px_20px_rgba(0,0,0,0.06)] z-20 rounded-b-2xl">
            <div class="w-full flex justify-end items-center gap-3">
                <x-ui.secondary-button
                    type="button"
                    @click="hide()"
                    :fullWidth="false"
                >
                    {{ tr('Close') }}
                </x-ui.secondary-button>

                @if(!$readonly)
                    @can('employees.edit')
                    <x-ui.primary-button
                        type="button"
                        x-show="!editMode"
                        x-transition
                        x-cloak
                        @click="enableEdit()"
                        :fullWidth="false"
                    >
                        <i class="fas fa-edit me-2"></i>
                        {{ tr('Edit') }}
                    </x-ui.primary-button>
                    @endcan

                    <x-ui.secondary-button
                        type="button"
                        x-show="editMode"
                        x-transition
                        x-cloak
                        @click="cancelEdit()"
                        :fullWidth="false"
                    >
                        {{ tr('Cancel Edit') }}
                    </x-ui.secondary-button>
                @endif
            </div>
        </div>
        </div>
        </x-ui.modal>
    </div>
    @endif
</div>