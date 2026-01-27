<div class="space-y-4 sm:space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {{-- Contract Type --}}
        <x-ui.select 
            :label="tr('Contract Type')" 
            model="contract_type" 
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
            wire:model="basic_salary" 
            value="{{ $basic_salary }}"
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

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
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
</div>



