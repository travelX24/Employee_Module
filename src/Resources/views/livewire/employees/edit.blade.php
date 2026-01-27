<div>
    {{-- Stepper Navigation --}}
    <div class="mb-6">
        <div class="flex justify-center items-center gap-2 overflow-x-auto pb-2">
            @foreach([
                1 => tr('Basic Information'),
                2 => tr('Job Information'),
                3 => tr('Financial Information'),
                4 => tr('Personal Information'),
                5 => tr('Documents'),
            ] as $stepNum => $stepLabel)
                <button 
                    type="button"
                    wire:click="$set('tab', {{ $stepNum }})"
                    class="group flex flex-col items-center gap-2 px-2 transition-all duration-200 min-w-[80px]"
                    :class="tab === {{ $stepNum }} ? 'scale-105' : 'hover:scale-105'"
                >
                    <div class="relative w-10 h-10 sm:w-12 sm:h-12 transition-all duration-200">
                        <svg viewBox="0 0 56 56" class="absolute inset-0 drop-shadow-sm">
                            <polygon 
                                points="28,4 48,20 40,48 16,48 8,20"
                                :fill="{{ $tab }} === {{ $stepNum }} ? 'var(--brand-via)' : ({{ $tab }} > {{ $stepNum }} ? 'var(--brand-via)' : '#f3f4f6')"
                                :stroke="{{ $tab }} === {{ $stepNum }} ? 'var(--brand-via)' : ({{ $tab }} > {{ $stepNum }} ? 'var(--brand-via)' : '#d1d5db')"
                                stroke-width="2.5"
                                class="transition-all duration-200"
                            />
                        </svg>
                        <div 
                            class="absolute inset-0 flex items-center justify-center text-sm font-extrabold transition-colors duration-200"
                            :class="{{ $tab }} === {{ $stepNum }} || {{ $tab }} > {{ $stepNum }} ? 'text-white' : 'text-gray-600'"
                        >
                            <span x-show="{{ $tab }} > {{ $stepNum }}">✓</span>
                            <span x-show="{{ $tab }} <= {{ $stepNum }}">{{ $stepNum }}</span>
                        </div>
                    </div>

                    <div 
                        class="text-[10px] sm:text-[11px] font-semibold text-center leading-tight transition-colors duration-200"
                        :class="{{ $tab }} === {{ $stepNum }} || {{ $tab }} > {{ $stepNum }} ? 'text-[color:var(--brand-via)]' : 'text-gray-500'"
                    >
                        {{ $stepLabel }}
                    </div>
                </button>

                @if(!$loop->last)
                    <div 
                        class="h-[3px] w-8 sm:w-12 rounded-full transition-all duration-300"
                        :class="{{ $tab }} > {{ $stepNum }} ? 'bg-gradient-to-r from-[color:var(--brand-via)] to-[color:var(--brand-via)]/80 shadow-sm' : 'bg-gray-200'"
                    ></div>
                @endif
            @endforeach
        </div>
    </div>

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            {{-- Tab 1: Basic Information --}}
            <div x-show="$wire.tab === 1" x-transition>
                @include('employees::livewire.employees.partials.tab-basic')
            </div>

            {{-- Tab 2: Job Information --}}
            <div x-show="$wire.tab === 2" x-transition>
                @include('employees::livewire.employees.partials.tab-job')
            </div>

            {{-- Tab 3: Financial Information --}}
            <div x-show="$wire.tab === 3" x-transition>
                @include('employees::livewire.employees.partials.tab-financial')
            </div>

            {{-- Tab 4: Personal Information --}}
            <div x-show="$wire.tab === 4" x-transition>
                @include('employees::livewire.employees.partials.tab-personal')
            </div>

            {{-- Tab 5: Documents --}}
            <div x-show="$wire.tab === 5" x-transition>
                @include('employees::livewire.employees.partials.tab-documents-edit')
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex justify-between gap-3 pt-4 border-t border-gray-200">
                <button
                    type="button"
                    wire:click="previousTab"
                    x-show="$wire.tab > 1"
                    class="px-6 py-3 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-2xl hover:bg-gray-50 hover:border-gray-400 hover:shadow-md active:scale-[0.97] transition-all duration-200 shadow-sm"
                >
                    <i class="fas fa-arrow-right me-2"></i>
                    {{ tr('Previous') }}
                </button>

                <div class="flex-1"></div>

                <button
                    type="button"
                    wire:click="nextTab"
                    x-show="$wire.tab < 5"
                    class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-[color:var(--brand-from)] via-[color:var(--brand-via)] to-[color:var(--brand-to)] rounded-2xl hover:shadow-lg active:scale-[0.97] transition-all duration-200 shadow-sm"
                >
                    {{ tr('Next') }}
                    <i class="fas fa-arrow-left ms-2"></i>
                </button>

                <button
                    type="submit"
                    x-show="$wire.tab === 5"
                    wire:loading.attr="disabled"
                    class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-[color:var(--brand-from)] via-[color:var(--brand-via)] to-[color:var(--brand-to)] rounded-2xl hover:shadow-lg active:scale-[0.97] transition-all duration-200 shadow-sm disabled:opacity-50"
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




