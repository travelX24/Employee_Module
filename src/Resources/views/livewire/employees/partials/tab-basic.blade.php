<div class="space-y-4 sm:space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {{-- Arabic Name --}}
        <x-ui.input 
            :label="tr('Arabic Name')" 
            wire:model="name_ar" 
            value="{{ $name_ar }}"
            error="name_ar" 
            :required="true"
        />

        {{-- English Name --}}
        <x-ui.input 
            :label="tr('English Name')" 
            wire:model="name_en" 
            value="{{ $name_en }}"
            error="name_en"
        />

        {{-- National ID --}}
        <x-ui.input 
            :label="tr('National ID')" 
            wire:model="national_id" 
            value="{{ $national_id }}"
            error="national_id" 
            :required="true"
        />

        {{-- Nationality --}}
        <x-ui.input 
            :label="tr('Nationality')" 
            wire:model="nationality" 
            value="{{ $nationality }}"
            error="nationality" 
            :required="true"
        />

        {{-- Gender --}}
        <x-ui.select 
            :label="tr('Gender')" 
            model="gender" 
            error="gender" 
            :required="true"
        >
            <option value="">{{ tr('Select Gender') }}</option>
            <option value="male" {{ $gender == 'male' ? 'selected' : '' }}>{{ tr('Male') }}</option>
            <option value="female" {{ $gender == 'female' ? 'selected' : '' }}>{{ tr('Female') }}</option>
        </x-ui.select>

        {{-- Birth Date --}}
        <x-ui.input 
            type="date"
            :label="tr('Birth Date')" 
            wire:model="birth_date" 
            value="{{ $birth_date }}"
            error="birth_date" 
            :required="true"
        />

        {{-- Birth Place --}}
        <x-ui.input 
            :label="tr('Birth Place')" 
            wire:model="birth_place" 
            value="{{ $birth_place }}"
            error="birth_place" 
            :required="true"
        />

        {{-- Social Status --}}
        <x-ui.select 
            :label="tr('Social Status')" 
            model="social_status" 
            error="social_status" 
            :required="true"
        >
            <option value="">{{ tr('Select Social Status') }}</option>
            <option value="single" {{ $social_status == 'single' ? 'selected' : '' }}>{{ tr('Single') }}</option>
            <option value="married" {{ $social_status == 'married' ? 'selected' : '' }}>{{ tr('Married') }}</option>
        </x-ui.select>

        {{-- Children Count --}}
        <x-ui.input 
            type="number"
            :label="tr('Children Count')" 
            wire:model="children_count" 
            value="{{ $children_count }}"
            error="children_count"
            min="0"
            placeholder="0"
        />
    </div>
</div>



