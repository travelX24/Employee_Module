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
        x-data="{
            activeTab: 1,
            editMode: false,
            employeeId: {{ $employee->id }},

            show() {
                this.editMode = false;
                this.activeTab = 1;
            },

            hide() {
                this.editMode = false;
                $wire.set('show', false);
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

            <div class="flex flex-col h-full overflow-hidden">
                {{-- Stepper (View mode only) --}}
        <div
            class="px-6 py-5 bg-gradient-to-br from-gray-50 via-gray-50/80 to-gray-50/60 border-b border-gray-200/50"
            x-show="!editMode"
        >
            {{-- Desktop / Tablet --}}
            <div class="hidden sm:block">
                <div class="flex justify-center overflow-x-auto no-scrollbar pb-2">

                    <div class="inline-flex items-start justify-center min-w-fit">
                        @foreach($steps as $stepNum => $stepLabel)
                            @php $isLast = $loop->last; @endphp

                            

                            <button
                                type="button"
                                @click="activeTab = {{ $stepNum }}"
                                class="group flex flex-col items-center gap-2 px-1 transition-all duration-200"
                                :class="activeTab === {{ $stepNum }} ? 'scale-105' : 'hover:scale-105'"
                            >
                                <div class="relative w-12 h-12 transition-all duration-200">
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
                                        class="absolute inset-0 flex items-center justify-center text-base font-extrabold transition-colors duration-200"
                                        :class="activeTab === {{ $stepNum }} || activeTab > {{ $stepNum }} ? 'text-white' : 'text-gray-600'"
                                    >
                                        <span x-show="activeTab > {{ $stepNum }}">✓</span>
                                        <span x-show="activeTab <= {{ $stepNum }}">{{ $stepNum }}</span>
                                    </div>
                                </div>

                                <div
                                    class="text-[11px] font-semibold text-center leading-tight max-w-[110px] transition-colors duration-200"
                                    :class="activeTab === {{ $stepNum }} || activeTab > {{ $stepNum }} ? 'text-[color:var(--brand-via)]' : 'text-gray-500'"
                                >
                                    {{ $stepLabel }}
                                </div>
                            </button>

                            @if(!$isLast)
                                <div
                                    class="h-[3px] w-16 md:w-20 lg:w-24 mx-3 md:mx-4 mt-6 rounded-full transition-all duration-300"
                                    :class="activeTab > {{ $stepNum }} ? 'bg-gradient-to-r from-[color:var(--brand-via)] to-[color:var(--brand-via)]/80 shadow-sm' : 'bg-gray-200'"
                                ></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="text-center mt-4">
                    <span class="text-sm font-bold text-[color:var(--brand-via)] bg-white/60 px-4 py-1.5 rounded-full inline-block shadow-sm">
                        {{ tr('Step') }} <span x-text="activeTab"></span> {{ tr('of') }} 6:
                        <span x-text="['{{ $steps[1] }}', '{{ $steps[2] }}', '{{ $steps[3] }}', '{{ $steps[4] }}', '{{ $steps[5] }}', '{{ $steps[6] }}'][activeTab - 1]"></span>
                    </span>
                </div>
            </div>

            {{-- Mobile --}}
            <div class="sm:hidden">
                <div class="flex justify-center overflow-x-auto no-scrollbar pb-2">

                    <div class="inline-flex items-center gap-2">
                        @foreach($steps as $stepNum => $stepLabel)
                            @php $isLast = $loop->last; @endphp

                            <button
                                type="button"
                                @click="activeTab = {{ $stepNum }}"
                                class="group transition-all duration-200"
                                :class="activeTab === {{ $stepNum }} ? 'scale-110' : ''"
                            >
                                <div class="relative w-10 h-10">
                                    <svg viewBox="0 0 56 56" class="absolute inset-0">
                                        <polygon
                                            points="28,4 48,20 40,48 16,48 8,20"
                                            :fill="activeTab === {{ $stepNum }} ? 'var(--brand-via)' : (activeTab > {{ $stepNum }} ? 'var(--brand-via)' : '#f3f4f6')"
                                            :stroke="activeTab === {{ $stepNum }} ? 'var(--brand-via)' : (activeTab > {{ $stepNum }} ? 'var(--brand-via)' : '#d1d5db')"
                                            stroke-width="2.5"
                                        />
                                    </svg>

                                    <div
                                        class="absolute inset-0 flex items-center justify-center text-sm font-extrabold"
                                        :class="activeTab === {{ $stepNum }} || activeTab > {{ $stepNum }} ? 'text-white' : 'text-gray-600'"
                                    >
                                        <span x-show="activeTab > {{ $stepNum }}">✓</span>
                                        <span x-show="activeTab <= {{ $stepNum }}">{{ $stepNum }}</span>
                                    </div>
                                </div>
                            </button>

                            @if(!$isLast)
                                <div
                                    class="h-[3px] w-7 rounded-full transition-all duration-300"
                                    :class="activeTab > {{ $stepNum }} ? 'bg-[color:var(--brand-via)]' : 'bg-gray-200'"
                                ></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="text-center mt-3">
                    <span class="text-xs font-bold text-[color:var(--brand-via)] bg-white/60 px-3 py-1 rounded-full inline-block">
                        {{ tr('Step') }} <span x-text="activeTab"></span> / 6 —
                        <span x-text="['{{ $steps[1] }}', '{{ $steps[2] }}', '{{ $steps[3] }}', '{{ $steps[4] }}', '{{ $steps[5] }}', '{{ $steps[6] }}'][activeTab - 1]"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto p-6">
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
                @livewire('employees.edit', ['employeeId' => $employee->id], 'edit-employee-'.$employee->id)
            </div>
</div>
</div>

            <x-slot name="footer">
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
                            @click="cancelEdit()"
                            :fullWidth="false"
                        >
                            {{ tr('Cancel Edit') }}
                        </x-ui.secondary-button>
                    @endif
                </div>
            </x-slot>
        </x-ui.modal>
    </div>
    @endif
</div>