<div class="space-y-4 sm:space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
    <x-ui.select
        :label="tr('Branch')"
        wire:model.live="branch_id"
        error="branch_id"
    >
        <option value="">{{ tr('Select Branch') }}</option>

        @foreach($this->branches as $branch)
            <option value="{{ $branch['value'] }}">
                {{ $branch['label'] }}
            </option>
        @endforeach
    </x-ui.select>



        {{-- Main Department --}}
        <x-ui.select 
            :label="tr('Main Department')" 
            wire:model.live="department_id" 
            error="department_id" 
            :required="true"
        >
            <option value="">{{ tr('Select Main Department') }}</option>
            @foreach($this->departments as $department)
                <option value="{{ $department['value'] }}" {{ $department_id == $department['value'] ? 'selected' : '' }}>{{ $department['label'] }}</option>
            @endforeach
        </x-ui.select>

        {{-- Sub Department --}}
        {{-- Sub Department --}}
        <div class="col-span-1" wire:key="sub-dept-wrapper-{{ $department_id }}">
            <x-ui.select 
                :label="tr('Sub Department')" 
                wire:model.live="sub_department_id" 
                error="sub_department_id"
            >
                <option value="">{{ tr('Select Sub Department') }}</option>
                @foreach($sub_departments as $subDepartment)
                    <option value="{{ $subDepartment['value'] }}" {{ $sub_department_id == $subDepartment['value'] ? 'selected' : '' }}>{{ $subDepartment['label'] }}</option>
                @endforeach
            </x-ui.select>
        </div>

        {{-- Job Title --}}
        <x-ui.select 
            :label="tr('Job Title')" 
            model="job_title_id" 
            error="job_title_id" 
            :required="true"
        >
            <option value="">{{ tr('Select Job Title') }}</option>
            @foreach($this->jobTitles as $jobTitle)
                <option value="{{ $jobTitle['value'] }}" {{ $job_title_id == $jobTitle['value'] ? 'selected' : '' }}>{{ $jobTitle['label'] }}</option>
            @endforeach
        </x-ui.select>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {{-- Grade --}}
        <x-ui.input 
            type="number"
            :label="tr('Grade')" 
            wire:model="grade" 
            value="{{ $grade }}"
            error="grade" 
            :required="true"
            min="1"
            max="10"
            placeholder="1-10"
        />

        {{-- Manager (Force Upwards Custom Dropdown) --}}
        <div class="space-y-2 relative" 
             x-data="{
                open: false,
                search: '',
                value: @entangle('manager_id').live,
                options: @js($this->managers),
                placeholder: '{{ tr('Select Manager') }}',

                init() {
                    this.$watch('open', val => {
                        if (val) {
                            this.$nextTick(() => this.$refs.searchInput.focus());
                        }
                    });
                },

                filtered() {
                    if (!this.search) return this.options;
                    return this.options.filter(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                },

                selectedLabel() {
                    const sel = this.options.find(o => String(o.id) === String(this.value));
                    return sel ? sel.name : '';
                },

                choose(opt) {
                    this.value = opt.id;
                    this.open = false;
                    this.search = '';
                }
             }"
             @click.away="open = false"
        >
            <label class="block text-sm font-semibold text-gray-700">{{ tr('Manager') }}</label>
            
            <div class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-[color:var(--brand-via)]/20 focus:border-[color:var(--brand-via)]
                           transition flex items-center justify-between"
                    :class="open ? 'ring-2 ring-[color:var(--brand-via)]/20 border-[color:var(--brand-via)]' : ''"
                >
                    <span class="truncate" :class="selectedLabel() ? 'text-gray-900' : 'text-gray-400'" x-text="selectedLabel() || placeholder"></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 transition" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="absolute z-[100] w-full bottom-full mb-2 rounded-xl border border-gray-200 bg-white shadow-2xl overflow-hidden"
                    style="display:none;"
                >
                    {{-- Search --}}
                    <div class="p-3 border-b border-gray-100 bg-white">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                x-ref="searchInput"
                                type="text"
                                x-model="search"
                                placeholder="{{ tr('Search...') }}"
                                class="w-full rounded-lg border border-gray-100 bg-gray-50 px-4 py-2 ps-9 text-xs focus:outline-none focus:ring-2 focus:ring-[color:var(--brand-via)]/30"
                            />
                        </div>
                    </div>

                    {{-- Items --}}
                    <div class="max-h-[220px] overflow-y-auto custom-scrollbar">
                        <template x-for="opt in filtered()" :key="opt.id">
                            <button
                                type="button"
                                @click="choose(opt)"
                                class="w-full text-start px-4 py-2.5 hover:bg-[color:var(--brand-via)]/5 transition flex items-center justify-between group border-b border-gray-50 last:border-0"
                            >
                                <span class="text-sm text-gray-700 group-hover:text-[color:var(--brand-via)] font-medium" x-text="opt.name"></span>
                                <i class="fas fa-check text-xs text-[color:var(--brand-via)]" x-show="String(value) === String(opt.id)"></i>
                            </button>
                        </template>
                        <div x-show="filtered().length === 0" class="p-4 text-xs text-gray-400 text-center">{{ tr('No results found') }}</div>
                    </div>

                    <style>
                        .custom-scrollbar::-webkit-scrollbar {
                            width: 4px;
                        }
                        .custom-scrollbar::-webkit-scrollbar-track {
                            background: #f1f1f1;
                        }
                        .custom-scrollbar::-webkit-scrollbar-thumb {
                            background: var(--brand-via);
                            border-radius: 10px;
                        }
                    </style>

                    {{-- Footer --}}
                    <div class="p-2 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
                        <button type="button" @click="value = null; open = false;" class="px-3 py-1 text-[10px] font-bold text-gray-500 hover:text-red-600 transition">{{ tr('Clear Selection') }}</button>
                        <button type="button" @click="open = false" class="px-3 py-1 text-[10px] font-bold text-gray-500 hover:text-gray-900 transition">{{ tr('Close') }}</button>
                    </div>
                </div>
            </div>
            
            @error('manager_id')
                <div class="text-xs text-red-600 font-semibold">{{ $message }}</div>
            @enderror
        </div>

        {{-- Hire Date --}}
        <x-ui.company-date-picker
            model="hired_at"
            :label="tr('Hire Date')"
        />
    </div>


</div>



