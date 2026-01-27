@props([
    'employee' => null,
    'readonly' => false,
])

@php
    if (!$employee) {
        return;
    }
    
    $steps = [
        1 => tr('Basic Information'),
        2 => tr('Job Information'),
        3 => tr('Financial Information'),
        4 => tr('Personal Information'),
        5 => tr('Documents'),
    ];
    
    $locale = app()->getLocale();
@endphp

<div
    x-data="{
        open: false,
        activeTab: 1,
        editMode: false,
        employeeId: {{ $employee->id }},
        show() {
            this.open = true;
            this.activeTab = 1;
            this.editMode = false;
            document.body.style.overflow = 'hidden';
        },
        hide() {
            this.open = false;
            this.editMode = false;
            document.body.style.overflow = '';
        },
        enableEdit() {
            this.editMode = true;
            this.activeTab = 1;
        },
        cancelEdit() {
            this.editMode = false;
            window.dispatchEvent(new CustomEvent('employee-edit-cancelled'));
        },
        refreshAndReopen() {
            this.hide();
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('open-view-employee-{{ $employee->id }}'));
            }, 500);
        }
    }"
    x-on:employee-updated.window="editMode = false; refreshAndReopen()"
    x-on:open-view-employee-{{ $employee->id }}.window="show()"
    x-on:keydown.escape.window="hide()"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="hide()"
        class="absolute inset-0 bg-gradient-to-br from-black/50 via-black/60 to-black/50 backdrop-blur-md"
    ></div>

    {{-- Modal Content --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        @click.away="hide()"
        class="relative bg-white rounded-3xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden ring-1 ring-black/5 flex flex-col"
    >
        {{-- Header --}}
        <div class="px-6 pt-5 pb-4 bg-gradient-to-br from-indigo-50 via-purple-50 to-cyan-50 border-b border-indigo-200/50 relative">
            <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent rounded-full blur-2xl"></div>
            </div>
            
            <div class="flex items-center justify-between relative z-10">
                <div class="min-w-0 flex-1 pr-4">
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ tr('Employee Details') }}
                    </h3>
                    <p class="text-sm text-gray-600 mt-1 mb-0 leading-normal">
                        @if($locale === 'ar')
                            {{ $employee->name_ar }}
                        @else
                            {{ $employee->name_en ?: $employee->name_ar }}
                        @endif
                    </p>
                </div>
                
                <button
                    type="button"
                    @click="hide()"
                    class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-xl text-gray-400 hover:text-gray-700 hover:bg-white/60 active:scale-95 transition-all duration-200 backdrop-blur-sm"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>

        {{-- Stepper (View Mode) --}}
        <div class="px-6 py-5 bg-gradient-to-br from-gray-50 via-gray-50/80 to-gray-50/60 border-b border-gray-200/50" x-show="!editMode">
            {{-- Desktop / Tablet --}}
            <div class="hidden sm:block">
                <div class="flex justify-center overflow-x-auto pb-2">
                    <div class="inline-flex items-start justify-center min-w-fit">
                        @foreach($steps as $stepNum => $stepLabel)
                            @php
                                $isLast = $loop->last;
                            @endphp
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
                        {{ tr('Step') }} <span x-text="activeTab"></span> {{ tr('of') }} 5: <span x-text="['{{ $steps[1] }}', '{{ $steps[2] }}', '{{ $steps[3] }}', '{{ $steps[4] }}', '{{ $steps[5] }}'][activeTab - 1]"></span>
                    </span>
                </div>
            </div>

            {{-- Mobile --}}
            <div class="sm:hidden">
                <div class="flex justify-center overflow-x-auto pb-2">
                    <div class="inline-flex items-center gap-2">
                        @foreach($steps as $stepNum => $stepLabel)
                            @php
                                $isLast = $loop->last;
                            @endphp
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
                        {{ tr('Step') }} <span x-text="activeTab"></span> / 5 — <span x-text="['{{ $steps[1] }}', '{{ $steps[2] }}', '{{ $steps[3] }}', '{{ $steps[4] }}', '{{ $steps[5] }}'][activeTab - 1]"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Content (Scrollable) --}}
        <div class="flex-1 overflow-y-auto p-6">
            {{-- View Mode --}}
            <div x-show="!editMode" x-transition>
                {{-- Tab 1: Basic Information --}}
                <div x-show="activeTab === 1" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-basic', ['employee' => $employee])
                </div>

                {{-- Tab 2: Job Information --}}
                <div x-show="activeTab === 2" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-job', ['employee' => $employee])
                </div>

                {{-- Tab 3: Financial Information --}}
                <div x-show="activeTab === 3" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-financial', ['employee' => $employee])
                </div>

                {{-- Tab 4: Personal Information --}}
                <div x-show="activeTab === 4" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-personal', ['employee' => $employee])
                </div>

                {{-- Tab 5: Documents --}}
                <div x-show="activeTab === 5" x-transition>
                    @include('employees::livewire.employees.partials.view-tab-documents', ['employee' => $employee])
                </div>
            </div>

            {{-- Edit Mode --}}
            <div x-show="editMode" x-transition class="w-full">
                @livewire('employees.edit', ['employeeId' => $employee->id], key('edit-employee-'.$employee->id))
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-white flex justify-between gap-3">
            {{-- Edit Button (View Mode) --}}
            @if(!$readonly)
                <button
                    type="button"
                    x-show="!editMode"
                    x-transition
                    @click="enableEdit()"
                    class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-[color:var(--brand-from)] via-[color:var(--brand-via)] to-[color:var(--brand-to)] rounded-2xl hover:shadow-lg active:scale-[0.97] transition-all duration-200 shadow-sm"
                >
                    <i class="fas fa-edit me-2"></i>
                    {{ tr('Edit') }}
                </button>
            @endif
            
            {{-- Cancel Button (Edit Mode) --}}
            @if(!$readonly)
                <button
                    type="button"
                    x-show="editMode"
                    x-transition
                    @click="cancelEdit()"
                    class="px-6 py-3 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 hover:border-gray-400 hover:shadow-md active:scale-[0.97] transition-all duration-200 shadow-sm"
                >
                    {{ tr('Cancel') }}
                </button>
            @endif
            
            <div class="flex gap-3">
                <button
                    type="button"
                    @click="hide()"
                    class="px-6 py-3 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 hover:border-gray-400 hover:shadow-md active:scale-[0.97] transition-all duration-200 shadow-sm"
                >
                    {{ tr('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>




