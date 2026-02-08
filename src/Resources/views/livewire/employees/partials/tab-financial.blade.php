<div class="space-y-5">
    {{-- قسم العقد والراتب الأساسي --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Contract Type --}}
        <x-ui.select 
            :label="tr('Contract Type')" 
            wire:model.live="contract_type" 
            error="contract_type" 
            :required="true"
        >
            <option value="">{{ tr('Select Contract Type') }}</option>
            <option value="permanent" {{ $contract_type == 'permanent' ? 'selected' : '' }}>{{ tr('Permanent') }}</option>
            <option value="temporary" {{ $contract_type == 'temporary' ? 'selected' : '' }}>{{ tr('Temporary') }}</option>
            <option value="probation" {{ $contract_type == 'probation' ? 'selected' : '' }}>{{ tr('Probation') }}</option>
            <option value="contractor" {{ $contract_type == 'contractor' ? 'selected' : '' }}>{{ tr('Contractor') }}</option>
        </x-ui.select>

        {{-- Basic Salary --}}
        <x-ui.input 
            type="number"
            step="0.01"
            :label="tr('Basic Salary')" 
            wire:model.blur="basic_salary" 
            error="basic_salary" 
            :required="true"
            placeholder="0.00"
        />

        {{-- Contract Duration --}}
        <x-ui.input 
            type="number"
            :label="tr('Contract Duration (Months)')" 
            wire:model="contract_duration_months" 
            value="{{ $contract_duration_months }}"
            error="contract_duration_months"
            min="1"
            placeholder="12"
        />
    </div>

    {{-- قسم الأجور المشتقة (ظهور فقط إذا كان هناك راتب أساسي) --}}
    @if(isset($daily_wage) && $daily_wage !== null)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="col-span-full mb-2">
                <h4 class="text-sm font-bold text-[color:var(--brand-via)] flex items-center gap-2">
                    <i class="fas fa-calculator"></i>
                    {{ tr('Calculated Wages') }} <span class="text-xs text-gray-500 font-normal">({{ tr('Auto-calculated based on work schedule') }})</span>
                </h4>
            </div>

            {{-- الأجر اليومي --}}
            <x-ui.input 
                type="text"
                :label="tr('Daily Wage')" 
                :value="number_format((float)$daily_wage, 0)"
                readonly
                class="bg-white text-gray-800 font-semibold border-gray-300"
            />

            {{-- الأجر بالساعة --}}
            <x-ui.input 
                type="text"
                :label="tr('Hourly Wage')" 
                :value="number_format((float)$hourly_wage, 2)"
                readonly
                class="bg-white text-gray-800 font-semibold border-gray-300"
            />

            {{-- الأجر بالدقيقة --}}
            <x-ui.input 
                type="text"
                :label="tr('Minute Wage')" 
                :value="number_format((float)$minute_wage, 2)"
                readonly
                class="bg-white text-gray-800 font-semibold border-gray-300"
            />
        </div>
    @endif

    {{-- قسم البدلات والإجازة البسيطة --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Allowance --}}
        <x-ui.input 
            type="number"
            step="0.01"
            :label="tr('Allowance')" 
            wire:model="allowance" 
            value="{{ $allowance }}"
            error="allowance"
            placeholder="0.00"
        />

        {{-- Annual Leave --}}
        <x-ui.input 
            type="number"
            :label="tr('Annual Leave Days')" 
            wire:model="annual_leave_days" 
            value="{{ $annual_leave_days }}"
            error="annual_leave_days"
            min="0"
            placeholder="21"
        />
    </div>

    {{-- قسم الإجازة السنوية المتقدم --}}
    @if(true)
        <div class="p-5 border-2 border-[color:var(--brand-via)]/20 rounded-xl bg-gradient-to-br from-[color:var(--brand-via)]/5 to-transparent">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-[color:var(--brand-via)]"></i>
                    {{ tr('Annual Leave Balance Management') }}
                </h3>
            </div>

            {{-- Checkbox موظف منقول --}}
            <div class="mb-4">
                <label class="inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        wire:model.live="is_transferred_employee" 
                        class="w-5 h-5 text-[color:var(--brand-via)] rounded border-gray-300 focus:ring-[color:var(--brand-via)]"
                    />
                    <span class="mr-3 text-sm font-semibold text-gray-700">{{ tr('Transferred Employee') }}</span>
                    <span class="text-xs text-gray-500">({{ tr('Employee transferred from another system') }})</span>
                </label>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @if($is_transferred_employee)
                    {{-- الرصيد الافتتاحي --}}
                    <div>
                        <x-ui.input 
                            type="number"
                            :label="tr('Opening Balance')" 
                            wire:model.live="opening_leave_balance" 
                            :value="$opening_leave_balance"
                            error="opening_leave_balance"
                            placeholder="0"
                        />
                        <p class="text-xs text-gray-500 mt-1">{{ tr('Can be negative') }}</p>
                    </div>
                @else
                    <div>
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg h-full flex items-center">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                {{ tr('Leave balance will be calculated automatically based on hire date') }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- الرصيد النهائي --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ tr('Current Balance') }}</label>
                    <div class="p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                        <div class="text-center">
                            <span class="text-3xl font-extrabold text-[color:var(--brand-via)]">{{ $calculated_leave_balance }}</span>
                            <p class="text-xs text-gray-600 font-medium mt-1">{{ tr('Available Days') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
